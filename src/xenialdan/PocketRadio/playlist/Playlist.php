<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\playlist;

use inxomnyaa\libnbs\Song;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use xenialdan\PocketRadio\event\AddPlayerToPlaylistEvent;
use xenialdan\PocketRadio\event\PlaylistModifySongsEvent;
use xenialdan\PocketRadio\event\PlaylistPlayEvent;
use xenialdan\PocketRadio\event\PlaylistStopPlayEvent;
use xenialdan\PocketRadio\event\RemovePlayerFromPlaylistEvent;
use xenialdan\PocketRadio\Loader;
use xenialdan\PocketRadio\task\PlaylistPlayTask;
use function array_rand;
use function basename;
use function current;
use function next;
use function reset;

class Playlist{
	public const MODE_LOOP = 0;
	public const MODE_RANDOM = 1;

	public const STATE_STOPPED = 0;
	public const STATE_PLAYING = 1;
	public const STATE_PAUSED = 2;

	public const PLAYLIST_PLUGIN_REASON = 0;//plugins should use this
	public const PLAYLIST_SONG_COMPLETE = 1;
	public const PLAYLIST_NO_PLAYERS = 2;
	public const PLAYLIST_PLAYER_ADDED = 3;
	public const PLAYLIST_NEXT_SONG = 4;
	public const PLAYLIST_SELECTED_SONG = 5;
	public const PLAYLIST_UNPAUSED = 6;

	/** @var array<string,Song> */
	private static array $songs = [];
	private array $players = [];
	private int $state = self::STATE_STOPPED;
	public ?TaskHandler $task = null;

	public function __construct(private string $name, private int $mode = self::MODE_LOOP, Song ...$songs){
		$this->addSongs(...$songs);
	}

	/** @return $this */
	public function addSongs(Song ...$songs) : self{
		$ev = new PlaylistModifySongsEvent($this, $songs, PlaylistModifySongsEvent::PLAYLIST_SONGS_ADDED);
		$ev->call();
		if(!$ev->isCancelled()){
			foreach($ev->getSongs() as $song){
				self::$songs[basename($song->getPath(), ".nbs")] = $song;
			}
		}
		return $this;
	}

	/** @return $this */
	public function removeSongs(Song ...$songs) : self{
		$ev = new PlaylistModifySongsEvent($this, $songs, PlaylistModifySongsEvent::PLAYLIST_SONGS_REMOVED);
		$ev->call();
		if(!$ev->isCancelled()){
			foreach($ev->getSongs() as $song){
				unset(self::$songs[basename($song->getPath(), ".nbs")]);
			}
		}
		return $this;
	}

	public function getSongs() : array{ return self::$songs; }

	public function getName() : string{ return $this->name; }

	public function getMode() : int{ return $this->mode; }

	/** @return $this */
	public function setMode(int $mode) : self{
		$this->mode = $mode;
		return $this;
	}

	public function getState() : int{ return $this->state; }

	public function stop(int $reason) : void{
		$ev = new PlaylistStopPlayEvent($this, $reason);
		$ev->call();
		if(!$ev->isCancelled()){
			$this->state = self::STATE_STOPPED;
			$this->task?->cancel();
			$this->task = null;
		}
	}

	public function play(int $reason) : void{
		$ev = new PlaylistPlayEvent($this, $reason);
		$ev->call();
		if(!$ev->isCancelled()){
			$this->state = self::STATE_PLAYING;
			if($this->task === null) $this->task = Loader::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new PlaylistPlayTask($this), 20 * 3, intval(floor($this->getCurrent()->getDelay())));
		}
	}

	public function pause() : void{
		$this->state = self::STATE_PAUSED;
	}

	public function getCount() : int{
		return count(self::$songs);
	}

	public function isEmpty() : bool{
		return empty(self::$songs);
	}

	public function getCurrent() : ?Song{
		$song = current(self::$songs);
		return $song === false ? null : $song;
	}

	public function getNext() : ?Song{
		if(empty(self::$songs)) return null;
		return match ($this->mode) {
			self::MODE_LOOP => $this->next(),
			self::MODE_RANDOM => $this->random(),
			default => throw new PlayListException($this, "Unknown mode: " . $this->mode)
		};
	}

	public function seek(string $songBaseName) : bool{
		if(!isset(self::$songs[$songBaseName])) return false;
		while(key(self::$songs) !== $songBaseName) $this->next();
		return true;
	}

	private function next() : ?Song{
		if(empty(self::$songs)) return null;
		if(($song = next(self::$songs)) === false) $song = reset(self::$songs);
		return $song === false ? null : $song;
	}

	private function random() : ?Song{
		if(empty(self::$songs)) return null;
		$this->seek(array_rand(self::$songs));
		return $this->getCurrent();
	}

	public function subscribe(Player $player) : void{
		$ev = new AddPlayerToPlaylistEvent($player, $this);
		$ev->call();
		if(!$ev->isCancelled()) $this->players[$player->getName()] = $player;
	}

	public function unsubscribe(Player $player) : void{
		$ev = new RemovePlayerFromPlaylistEvent($player, $this);
		$ev->call();
		if(!$ev->isCancelled()) unset($this->players[$player->getName()]);
	}

	public function getPlayers() : array{ return $this->players; }

	public function __toString() : string{
		return $this->name . " mode: " . ($this->mode === self::MODE_LOOP ? "loop" : "random") . " songs: " . count(self::$songs) . " players: " . count($this->players);
	}

}