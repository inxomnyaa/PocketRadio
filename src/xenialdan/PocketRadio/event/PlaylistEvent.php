<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\event;

use pocketmine\event\Event;
use xenialdan\PocketRadio\playlist\Playlist;

abstract class PlaylistEvent extends Event{
	public function __construct(private Playlist $playlist){
	}

	public function getPlaylist() : Playlist{ return $this->playlist; }

}