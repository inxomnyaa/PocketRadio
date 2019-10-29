<?php

namespace xenialdan\PocketRadio;

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use xenialdan\libnbs\Layer;
use xenialdan\libnbs\NBSFile;
use xenialdan\PocketRadio\commands\RadioCommand;

class Loader extends PluginBase
{

    /** @var Loader */
    private static $instance = null;

    /* Configs */
    /** @var Config */
    private static $volumeConfig;
    /** @var [] */
    public static $songlist = [];
    /** @var [] */
    private static $playlist = [];
    /* Songs */
    public static $tasks = [];

    /**
     * Returns an instance of the plugin
     * @return Loader
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function onLoad()
    {
        self::$instance = $this;
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->registerAll("pocketradio", [new RadioCommand($this)]);
        @mkdir($this->getDataFolder() . "songs");
        self::$songlist = glob($this->getDataFolder() . "songs/*.nbs");
        $this->saveResource("volume.yml");
        self::$volumeConfig = new Config($this->getDataFolder() . "volume.yml");
    }

    public function onDisable()
    {
        $all = self::$volumeConfig->getAll();
        foreach (self::$volumeConfig->getAll(true) as $key) {
            self::$volumeConfig->remove($key);
        }
        $all = array_filter($all, function ($value) {
            return floor($value) != 100.0; // Remove unchanged values
        });
        self::$volumeConfig->setAll($all);
        self::$volumeConfig->save();
    }

    public static function getRandomSong()
    {
        if (empty(self::$songlist)) return "";
        return self::$songlist[array_rand(self::$songlist)];
    }

    public static function addToPlaylist($filename)
    {
        self::$playlist[] = $filename;
    }

    public static function getNextSong()
    {
        $song = array_pop(self::$playlist);
        if (is_null($song))
            $song = self::getRandomSong();
        return $song;
    }

    /**
     * @param Player $player
     * @return bool|float|null
     */
    public static function getVolume(Player $player)
    {
        return self::$volumeConfig->get($player->getName(), 100);
    }

    public static function getSoundVolume(Player $player)
    {
        $volumePercentage = self::getVolume($player) / 100;
        return 50 - (50 * $volumePercentage);
    }

    public static function setVolume(Player $player, float $volume)
    {
        self::$volumeConfig->set($player->getName(), $volume);
    }

    public static function playNext()
    {
        self::getInstance()->getScheduler()->cancelAllTasks();
        self::getInstance()->startTask();
    }

    public function startTask()
    {
        $songfilename = self::getNextSong();
        if ($songfilename === "") {
            $this->getLogger()->warning("Could not start radio: No music found / given");
            return;
        }

        $song = null;
        try {
            $song = new NBSFile($songfilename);
        } catch (PluginException $exception) {
            $this->getLogger()->warning("This song could not be read: " . $songfilename);
            $this->getLogger()->warning($exception->getMessage());
            $this->getLogger()->warning($exception->getTraceAsString());
        }
        if (!$song instanceof NBSFile) {
            $this->getLogger()->debug("Starting a new song because the current song could not be played");
            $this->startTask();
            return;
        }
        $basename = basename($songfilename, ".nbs");
        $this->getScheduler()->scheduleDelayedRepeatingTask(new class($this, $basename, $song) extends Task
        {
            public $song = null;
            public $songfilename = "";
            public $currentLength = 0;
            /** @var Plugin|Loader */
            public $owner;

            public function __construct(Plugin $owner, string $songfilename, NBSFile $song)
            {
                $this->owner = $owner;
                $this->song = $song;
                $this->songfilename = $songfilename;
                Loader::$tasks[] = $this->getTaskId();
                $owner->getServer()->broadcastMessage(TextFormat::GREEN . $this->owner->getDescription()->getPrefix() . " Now playing: " . (empty($this->song->name) ? $songfilename : $this->song->name) . (empty($this->song->originalAuthor) ? "" : " by " . $this->song->originalAuthor));
            }

            public function onRun(int $currentTick)
            {
                if ($this->currentLength > $this->song->length) {
                    $this->getHandler()->cancel();
                    Loader::getInstance()->startTask();
                    return;
                }
                $notes = $this->song->getNotesAtTick(floor($this->currentLength));
                $this->currentLength++;
                if (empty($notes)) return;
                foreach ($notes as $note) {
                    /** @var Layer $layer */
                    $layer = $this->song->getLayerInfo()[$note->layer];
                    $pk = new PlaySoundPacket();
                    //TODO custom sound support, figure out path in resource pack
                    $pk->soundName = NBSFile::MAPPING[$note->instrument] ?? NBSFile::MAPPING[NBSFile::INSTRUMENT_PIANO];
                    $pk->volume = ($layer->volume ?? 100) / 100 * 3;
                    $pk->pitch = intval($note->key - 33);
                    foreach ($this->owner->getServer()->getOnlinePlayers() as $player) {
                        $vector = $player->asVector3();
                        if ($layer->stereo !== 100) {//Not centered, modify position. TODO check
                            $yaw = ($player->yaw - 90) % 360;
                            $add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();
                            $multiplier = 2 * ($layer->stereo - 100) / 100;
                            $add = $add->multiply($multiplier);
                            $vector->add($add->x, 0, $add->y);
                            unset($add);
                        }
                        $pk->x = $vector->x;
                        $pk->y = $vector->y - Loader::getSoundVolume($player) + 1;
                        $pk->z = $vector->z;
                        $player->dataPacket($pk);
                        unset($vector);
                    }
                    unset($pk);
                }
            }
        }, 20 * 3, floor($song->tempo / 100) / 2.5);
    }
}