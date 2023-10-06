<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\task;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use inxomnyaa\libnbs\NBSFile;
use inxomnyaa\libnbs\Song;
use xenialdan\PocketRadio\Loader;

class SongPlayerTask extends Task{
	public ?Song $song = null;
	public string $songfilename = "";
	/** @var bool */
	protected bool $playing = false;
	/** @var int */
	private int $tick = -1;

	public function __construct( string $songfilename, Song $song){
		$this->song = $song;
		$this->songfilename = $songfilename;
		$this->playing = true;
		Loader::getInstance()->getServer()->broadcastMessage(TextFormat::GREEN . Loader::getInstance()->getDescription()->getPrefix() . " Now playing: " . $this->song->getSongTitleAndAuthorInfo());
	}

	public function onRun() : void{
		if(!$this->playing){
			return;
		}
		if($this->tick > $this->song->getLength()){
			$this->tick = -1;
			$this->playing = false;
			Loader::getInstance()::playNext();
			return;
		}
		$this->tick++;
		foreach(Loader::getInstance()->getServer()->getOnlinePlayers() as $player)
			$this->playTick($player, $this->tick);
	}

	public function playTick(Player $player, int $tick) : void{
		foreach($this->song->getLayerHashMap() as $layer){
			$note = $layer->getNote($tick);
			if($note === null){
				continue;
			}

			$volume = (($layer->getVolume() * Loader::getVolume($player)) / 10000);
			//shift nbs range for note block sounds (33 - 57) to start at 0
			//then shift by some extra -12 for the
			//pitch calculation: https://minecraft.gamepedia.com/Note_Block#Notes
			$pitch = 2 ** (($note->getKey() - 45) / 12);
			$sound = NBSFile::mapping($note->instrument);
			//TODO custom sound support, figure out path in resource pack
			$vector = $player->getPosition()->asVector3();
			/*if ($layer->stereo !== 100) {//Not centered, modify position. TODO fix
				$yaw = ($player->yaw - 90) % 360;
				$add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();//use v3->dot?
				$multiplier = 2 * (($layer->stereo - 100) / 100);
				$add = $add->multiply($multiplier);
				$vector->add($add->x, 0, $add->y);
			}*/
			$player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($sound, $vector->x, $vector->y + $player->getEyeHeight(), $vector->z, $volume, $pitch));
			unset($add, $pk, $vector, $note);
		}
	}
}
