<?php

namespace xenialdan\PocketRadio;

use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use xenialdan\libnbs\NBSFile;
use xenialdan\PocketRadio\commands\RadioCommand;

class Loader extends PluginBase{

	/** @var Loader */
	private static $instance = null;

	/* Configs */
	/** @var Config */
	private static $volumeConfig;
	/** @var [] */
	private static $songlist = [];
	/** @var [] */
	private static $playlist = [];
	/* Songs */
	public static $tasks = [];

	/**
	 * Returns an instance of the plugin
	 * @return Loader
	 */
	public static function getInstance(){
		return self::$instance;
	}

	public function onLoad(){
		self::$instance = $this;
	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getServer()->getCommandMap()->registerAll("pocketradio", [new RadioCommand($this)]);
		@mkdir($this->getDataFolder() . "songs");
		self::$songlist = glob($this->getDataFolder() . "songs/*.nbs");
		$this->saveResource("volume.yml");
		self::$volumeConfig = new Config($this->getDataFolder() . "volume.yml");
	}

	public function onDisable(){
		$all = self::$volumeConfig->getAll();
		foreach (self::$volumeConfig->getAll(true) as $key){
			self::$volumeConfig->remove($key);
		}
		$all = array_filter($all, function ($value){
			return floor($value) != 100.0; // Remove unchanged values
		});
		self::$volumeConfig->setAll($all);
		self::$volumeConfig->save();
	}

	public static function getRandomSong(){
		if (empty(self::$songlist)) return "";
		return self::$songlist[array_rand(self::$songlist)];
	}

	public static function addToPlaylist($filename){
		self::$playlist[] = $filename;
	}

	public static function getNextSong(){
		$song = array_pop(self::$playlist);
		if (is_null($song))
			$song = self::getRandomSong();
		return $song;
	}

	/**
	 * @param Player $player
	 * @return bool|float|null
	 */
	public static function getVolume(Player $player){
		return self::$volumeConfig->get($player->getName(), 100);
	}

	public static function getSoundVolume(Player $player){
		$volumePercentage = self::getVolume($player) / 100;
		return 50 - (50 * $volumePercentage);
	}

	public static function setVolume(Player $player, float $volume){
		self::$volumeConfig->set($player->getName(), $volume);
	}

	public function startTask(){
		$songfilename = self::getNextSong();
		if ($songfilename === ""){
			$this->getLogger()->warning("Could not start radio: No music found / given");
			return;
		}

		$song = null;
		try{
			$song = new NBSFile($songfilename);
		} catch (PluginException $exception){
			$this->getLogger()->warning("This song could not be read: " . $songfilename);
			$this->getLogger()->warning($exception->getMessage());
			$this->getLogger()->warning($exception->getTraceAsString());
		}
		if (!$song instanceof NBSFile){
			$this->getLogger()->debug("Starting a new song because the current song could not be played");
			$this->startTask();
			return;
		}
		$basename = basename($songfilename, ".nbs");
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new class($this, $basename, $song) extends PluginTask{
			public $song = null;
			public $songfilename = "";
			public $currentLength = 0;

			public function __construct(Plugin $owner, string $songfilename, NBSFile $song){
				parent::__construct($owner);
				$this->song = $song;
				$this->songfilename = $songfilename;
				Loader::$tasks[] = $this->getTaskId();
				$owner->getServer()->broadcastMessage(TextFormat::GREEN . $this->owner->getDescription()->getPrefix() . " Now playing: " . (empty($this->song->name) ? $songfilename : $this->song->name) . (empty($this->song->originalAuthor) ? "" : " by " . $this->song->originalAuthor));
			}

			public function onRun(int $currentTick){
				if ($this->currentLength > $this->song->length){
					$this->getHandler()->cancel();
					Loader::getInstance()->startTask();
					return;
				}
				$notes = $this->song->getNotesAtTick(floor($this->currentLength));
				$this->currentLength++;
				if (empty($notes)) return;
				foreach ($notes as $note){
					#$pk = new PlaySoundPacket();
					$pk = new LevelSoundEventPacket();
					$pk->sound = LevelSoundEventPacket::SOUND_NOTE;
					switch ($value = $note->instrument){
						case NBSFile::INSTRUMENT_PIANO: {
							#$pk->soundName = "note.harp";
							$pk->extraData = 0;
							break;
						}
						case NBSFile::INSTRUMENT_DOUBLE_BASS: {
							#$pk->soundName = "note.bassattack";
							$pk->extraData = 4;
							break;
						}
						case NBSFile::INSTRUMENT_BASS_DRUM: {
							#$pk->soundName = "note.bd";
							$pk->extraData = 1;
							break;
						}
						case NBSFile::INSTRUMENT_SNARE: {
							#$pk->soundName = "note.snare";
							$pk->extraData = 2;
							break;
						}
						case NBSFile::INSTRUMENT_CLICK: {
							#$pk->soundName = "note.hat";
							$pk->extraData = 3;
							break;
						}
						case NBSFile::INSTRUMENT_GUITAR: {
							#$pk->soundName = "note.pling";
							$pk->extraData = $value;
							break;
						}
						case NBSFile::INSTRUMENT_FLUTE: {//TODO
							#$pk->soundName = "note.harp";
							$pk->extraData = $value;
							break;
						}
						case NBSFile::INSTRUMENT_BELL: {//TODO
							#$pk->soundName = "note.harp";
							$pk->extraData = $value;
							break;
						}
						case NBSFile::INSTRUMENT_CHIME: {//TODO
							#$pk->soundName = "note.harp";
							$pk->extraData = $value;
							break;
						}
						case NBSFile::INSTRUMENT_XYLOPHONE: {
							#$pk->soundName = "note.harp";//TODO
							$pk->extraData = $value;
							break;
						}
					}
					#$pk->volume = $this->song->getLayerInfo()[$note->layer]??100;
					$pk->pitch = intval($note->key - 33);
					foreach ($this->getOwner()->getServer()->getOnlinePlayers() as $player){
						$pk2 = clone $pk;
						#$pk2->x = $player->x;
						#$pk2->y = $player->y;
						#$pk2->z = $player->z;
						$pk2->position = $player->asVector3()->add(0, -Loader::getSoundVolume($player) + 1);
						$player->dataPacket($pk2);
					}
				}
			}
		}, 20 * 3, floor($song->tempo / 100) / 2.5);
	}
}