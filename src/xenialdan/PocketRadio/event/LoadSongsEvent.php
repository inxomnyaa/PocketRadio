<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\event;

use pocketmine\event\Event;

class LoadSongsEvent extends Event{
	public function __construct(private string $name, private array $songs, private array $errors){
	}

	public function getPlaylistName() : string{ return $this->name; }

	public function getSongs() : array{ return $this->songs; }

	public function getErrors() : array{ return $this->errors; }

}