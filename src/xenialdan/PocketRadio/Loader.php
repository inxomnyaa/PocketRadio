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
                $server->getLogger()->info("Loaded " . count($songlist) . " songs");
                $songlist = array_values($songlist);
                Loader::$songlist = $songlist;
                foreach ($errors as $i => $error) {
                    Loader::getInstance()->getLogger()->error($error);
                    if ($i > 5) break;
                    if ($i === 0) print next($songlist);
                }
                #Loader::playNext();
            }
        });
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->registerAll("pocketradio", [new RadioCommand($this)]);
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
        self::$songlist = [];
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
     * @return bool|float|null
     */
    public static function getVolume(Player $player)
    {
        return self::$volumeConfig->get($player->getName(), 100);
    }

    public static function getSoundVolume(Player $player)
    {
        return self::getVolume($player) / 100;
    }

    public static function setVolume(Player $player, float $volume)
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