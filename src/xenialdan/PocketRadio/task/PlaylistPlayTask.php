<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\task;

use inxomnyaa\libnbs\NBSFile;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;
use xenialdan\PocketRadio\playlist\Playlist;

class PlaylistPlayTask extends Task{
	/** @var int */
	private int $tick = -1;

	public function __construct(private Playlist $playlist){
		Loader::getInstance()->getServer()->broadcastMessage(TextFormat::GREEN . "Now playing: " . $playlist->getCurrent()->getSongTitleAndAuthorInfo(), $this->playlist->getPlayers());
	}

	public function onRun() : void{
		if($this->playlist->getState() !== Playlist::STATE_PLAYING){
			return;
		}
		if($this->tick > $this->playlist->getCurrent()->getLength()){
			$this->playlist->stop(Playlist::PLAYLIST_SONG_COMPLETE);
			$this->playlist->getNext();
			$this->playlist->play(Playlist::PLAYLIST_SONG_COMPLETE);
			return;
		}
		$this->tick++;
		$this->playTick($this->tick);
	}

	public function playTick(int $tick) : void{
		foreach($this->playlist->getCurrent()->getLayerHashMap() as $layer){
			$note = $layer->getNote($tick);
			if($note === null){
				continue;
			}

			//shift nbs range for note block sounds (33 - 57) to start at 0
			//then shift by some extra -12 for the
			//pitch calculation: https://minecraft.gamepedia.com/Note_Block#Notes
			$pitch = 2 ** (($note->getKey() - 45) / 12);
			$sound = NBSFile::mapping($note->instrument);
			//TODO custom sound support, figure out path in resource pack
			foreach($this->playlist->getPlayers() as $player){
				if(Loader::getVolume($player) === 0){
					continue;
				}
				$volume = (($layer->getVolume() * Loader::getVolume($player)) / 10000);
				$vector = $player->getEyePos()->asVector3();
				/*if ($layer->stereo !== 100) {//Not centered, modify position. TODO fix
					$yaw = ($player->yaw - 90) % 360;
					$add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();//use v3->dot?
					$multiplier = 2 * (($layer->stereo - 100) / 100);
					$add = $add->multiply($multiplier);
					$vector->add($add->x, 0, $add->y);
				}*/
				$player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($sound, $vector->x, $vector->y, $vector->z, $volume, $pitch));
			}
			unset($vector, $note);
		}
	}
}
