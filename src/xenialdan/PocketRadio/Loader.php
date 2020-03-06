<?php

namespace xenialdan\PocketRadio;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use xenialdan\libnbs\NBSFile;
use xenialdan\libnbs\Song;
use xenialdan\PocketRadio\commands\RadioCommand;
use xenialdan\PocketRadio\task\SongPlayerTask;

class Loader extends PluginBase
{

    /** @var Loader */
    private static $instance = null;

    /* Configs */
    /** @var Config */
    private static $volumeConfig;
    /** @var Song[] */
    public static $songlist = [];
    /* Songs */
    public static $tasks = [];
    /** @var int */
    public const DEFAULT_VOLUME = 50;

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
        $songPath = $this->getDataFolder() . "songs";
        @mkdir($songPath);
        self::$volumeConfig = new Config($this->getDataFolder() . "volume.yml");
        $this->getServer()->getAsyncPool()->submitTask(new class($songPath) extends AsyncTask
        {
            private $songPath;

            public function __construct(string $songPath)
            {
                $this->songPath = $songPath;
            }

            /**
             * Actions to execute when run
             *
             * @return void
             */
            public function onRun()
            {
                $list = [];
                $errors = [];
                foreach (glob($this->songPath . DIRECTORY_SEPARATOR . "*.nbs") as $path) {
                    try {
                        $song = NBSFile::parse($path);
                        if ($song !== null) $list[] = $song;
                    } catch (\Exception $e) {
                        //TODO logger debug output
                        $errors[] = "This song could not be read: " . basename($path, ".nbs");
                        $errors[] = $e->getMessage();
                        $errors[] = $e->getTraceAsString();
                    }
                }
                $this->setResult(compact("list", "errors"));
            }

            public function onCompletion(Server $server)
            {
                $result = $this->getResult();
                /**
                 * @var Song[] $songlist
                 * @var string[] $errors
                 */
                [$songlist, $errors] = [$result["list"], $result["errors"]];
                Loader::getInstance()->getLogger()->info("Loaded " . count($songlist) . " songs");
                $songlist = array_values($songlist);
                Loader::$songlist = $songlist;
                foreach ($errors as $i => $error) {
                    Loader::getInstance()->getLogger()->error($error);
                    if ($i > 5) break;
                    next($songlist);
                }
            }
        });
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->registerAll("pocketradio", [new RadioCommand($this)]);
    }

    public function onDisable()
    {
        self::$songlist = [];
        $all = array_filter(self::$volumeConfig->getAll(), function ($value) {
            return ((int)floor($value)) !== self::DEFAULT_VOLUME; // Remove unchanged values
        });
        self::$volumeConfig->setAll($all);
        self::$volumeConfig->save();
    }

    public static function getRandomSong(): ?Song
    {
        if (empty(self::$songlist)) return null;
        return self::$songlist[array_rand(self::$songlist)];
    }

    public static function getNextSong(): ?Song
    {
        $song = next(self::$songlist);
        if ($song === false) $song = reset(self::$songlist);
        if ($song === false) return null;
        #if (is_null($song))
        #    $song = self::getRandomSong();
        return $song;
    }

    /**
     * @param Player $player
     * @return int 0...100
     */
    public static function getVolume(Player $player)
    {
        return self::$volumeConfig->get($player->getName(), self::DEFAULT_VOLUME);
    }

    /**
     * @param Player $player
     * @return float 0...1
     */
    public static function getSoundVolume(Player $player)
    {
        return self::getVolume($player) / 100;
    }

    public static function setVolume(Player $player, int $volume)
    {
        self::$volumeConfig->set($player->getName(), $volume);
    }

    public static function playNext(?Song $song = null)
    {
        self::getInstance()->getScheduler()->cancelAllTasks();
        self::getInstance()->startTask($song);
    }

    public function startTask(?Song $song = null)
    {
        $song = $song ?? self::getNextSong();
        if (!$song instanceof Song) {
            $this->getLogger()->warning("Could not start radio: No music found / given");
            return;
        }
        $this->getScheduler()->scheduleDelayedRepeatingTask(new SongPlayerTask($this, $song->getPath(), $song), 20 * 3, intval(floor($song->getDelay())));
    }
}