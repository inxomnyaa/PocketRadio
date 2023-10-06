<?php

namespace xenialdan\PocketRadio;

use CortexPE\Commando\PacketHooker;
use Exception;
use GlobIterator;
use inxomnyaa\libnbs\NBSFile;
use inxomnyaa\libnbs\Song;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Config;
use xenialdan\PocketRadio\commands\RadioCommand;
use xenialdan\PocketRadio\playlist\Playlist;

class Loader extends PluginBase{
	private static ?Loader $instance = null;

	/* Configs */
	private static Config $volumeConfig;
	public static Playlist $serverPlaylist;
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
//		$playlistPath = $this->getDataFolder() . "playlists";
		@mkdir($songPath);
//		@mkdir($playlistPath);
		self::$volumeConfig = new Config($this->getDataFolder() . "volume.yml");
		self::$serverPlaylist = new Playlist("Server Playlist", Playlist::MODE_RANDOM);
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
				Loader::$serverPlaylist->addSongs(...$songlist);
			}
		});
	}

	public function onEnable() : void{
		if(!PacketHooker::isRegistered()) PacketHooker::register($this);
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->getServer()->getCommandMap()->register("pocketradio", new RadioCommand($this, "radio"));
	}

	public function onDisable() : void{
		$this->getScheduler()->cancelAllTasks();
		$all = array_filter(self::$volumeConfig->getAll(), fn($value) => ((int) floor($value)) !== self::DEFAULT_VOLUME);//remove default volume
		self::$volumeConfig->setAll($all);
		self::$volumeConfig->save();
	}

	public static function getVolume(Player $player) : int{
		return self::$volumeConfig->get($player->getName(), self::DEFAULT_VOLUME);
	}

	public static function setVolume(Player $player, int $volume) : void{
		self::$volumeConfig->set($player->getName(), $volume);
	}
}