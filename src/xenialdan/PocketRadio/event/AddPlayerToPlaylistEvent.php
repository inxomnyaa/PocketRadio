<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use xenialdan\PocketRadio\playlist\Playlist;

class AddPlayerToPlaylistEvent extends PlaylistEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(private Player $player, Playlist $playlist){
		parent::__construct($playlist);
	}

	public function getPlayer() : Player{ return $this->player; }
}