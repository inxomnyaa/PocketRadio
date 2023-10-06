<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\playlist;

use Exception;
use Throwable;

class PlayListException extends Exception{
	public function __construct(Playlist $playlist, $message = "", $code = 0, Throwable $previous = null){
		parent::__construct($playlist->getName() . ": " . $message, $code, $previous);
	}
}