<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\event;

use inxomnyaa\libnbs\Song;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use xenialdan\PocketRadio\playlist\Playlist;

class PlaylistStopPlayEvent extends PlaylistEvent implements Cancellable{
	use CancellableTrait;
	public function __construct(Playlist $playlist, private int $reason){
		parent::__construct($playlist);
	}

	public function getSong() : ?Song{ return $this->getPlaylist()->getCurrent(); }

	public function getReason() : int{ return $this->reason; }
}