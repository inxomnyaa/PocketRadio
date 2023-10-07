<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\event;

use inxomnyaa\libnbs\Song;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use xenialdan\PocketRadio\playlist\Playlist;

class PlaylistModifySongsEvent extends PlaylistEvent implements Cancellable{
	use CancellableTrait;

	public const PLAYLIST_SONGS_ADDED = 0;
	public const PLAYLIST_SONGS_REMOVED = 1;

	public function __construct(Playlist $playlist, private array $songs, private int $reason){
		parent::__construct($playlist);
	}

	/** @return Song[] */
	public function getSongs() : array{ return $this->songs; }

	/** @param Song[] $songs */
	public function setSongs(array $songs) : void{ $this->songs = $songs; }

	public function getReason() : int{ return $this->reason; }
}