<?php

namespace xenialdan\PocketRadio;

use CortexPE\Commando\PacketHooker;
use Exception;
use GlobIterator;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Config;
use inxomnyaa\libnbs\NBSFile;
use inxomnyaa\libnbs\Song;
use xenialdan\PocketRadio\commands\RadioCommand;
use xenialdan\PocketRadio\task\SongPlayerTask;
use function basename;
use function var_dump;

class Loader extends PluginBase{
	private static ?Loader $instance = null;

	/* Configs */
	private static Config $volumeConfig;
	/** @var array<string,Song> */
	public static array $songlist = [];
	public static ?TaskHandler $task = null;
	public const DEFAULT_VOLUME = 50;

	/**
	 * Returns an instance of the plugin
	 * @return Loader|null
	 */
	public static function getInstance() : ?Loader{
		return self::$instance;
	}

	public function onLoad() : void{
		self::$instance = $this;
		$songPath = $this->getDataFolder() . "songs";
		@mkdir($songPath);
		self::$volumeConfig = new Config($this->getDataFolder() . "volume.yml");
		$this->getServer()->getAsyncPool()->submitTask(new class($songPath) extends AsyncTask{
			public function __construct(private string $songPath){ }

			public function onRun() : void{
				$list = [];
				$errors = [];
				$globIterator = new GlobIterator($this->songPath . DIRECTORY_SEPARATOR . "*.nbs");
				while($globIterator->valid()){
					try{
						$song = NBSFile::parse($globIterator->current()->getRealPath());
						$list[$globIterator->current()->getBasename(".nbs")] = $song;
					}catch(Exception $e){
						//TODO logger debug output
						$errors[] = "Song could not be read: " . $globIterator->current()->getBasename(".nbs");
						$errors[] = $e->getMessage();
						$errors[] = $e->getTraceAsString();
					}
					$globIterator->next();
				}
				$this->setResult(compact("list", "errors"));
			}

			public function onCompletion() : void{
				$result = $this->getResult();
				/**
				 * @var Song[]   $songlist
				 * @var string[] $errors
				 */
				[$songlist, $errors] = [$result["list"] ?? [], $result["errors"] ?? []];
				Loader::getInstance()->getLogger()->info("Loaded " . count($songlist) . " songs");
				foreach($errors as $error){
					Loader::getInstance()->getLogger()->error($error);
				}
				Loader::$songlist = $songlist;
			}
		});
	}

	public function onEnable() : void{
		if(!PacketHooker::isRegistered()) PacketHooker::register($this);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->getServer()->getCommandMap()->register("pocketradio", new RadioCommand($this, "radio"));
	}

	public function onDisable() : void{
		$this->stopTask();
		self::$songlist = [];
		$all = array_filter(self::$volumeConfig->getAll(), fn($value) => ((int) floor($value)) !== self::DEFAULT_VOLUME);//remove default volume
		self::$volumeConfig->setAll($all);
		self::$volumeConfig->save();
	}

	public static function getCurrentSong() : ?Song{
		$song = current(self::$songlist);
		return $song === false ? null : $song;
	}

	public static function getRandomSong() : ?Song{
		return empty(self::$songlist) ? null : self::$songlist[array_rand(self::$songlist)];
	}

	public static function getNextSong() : ?Song{
		$song = next(self::$songlist);
		if($song === false) $song = reset(self::$songlist);
		if($song === false) $song = self::getRandomSong();//TODO this will only be called if there are no songs, TODO add play queue/playlist
		return $song;
	}

	public static function getVolume(Player $player) : int{
		return self::$volumeConfig->get($player->getName(), self::DEFAULT_VOLUME);
	}

	public static function setVolume(Player $player, int $volume) : void{
		self::$volumeConfig->set($player->getName(), $volume);
	}

	public static function playNext(?Song $song = null) : void{//TODO set playlist pointer?
		self::getInstance()->startTask($song);
	}

	public function startTask(?Song $song = null) : void{
		$song = $song ?? self::getNextSong();
		if(!$song instanceof Song){
			$this->getLogger()->warning("Could not start radio: No music found / given");
			return;
		}
		$this->stopTask();
		Loader::$task = $this->getScheduler()->scheduleDelayedRepeatingTask(new SongPlayerTask($song->getPath(), $song), 20 * 3, intval(floor($song->getDelay())));
	}

	public function stopTask() : void{
		Loader::$task?->cancel();
		Loader::$task = null;
	}
}