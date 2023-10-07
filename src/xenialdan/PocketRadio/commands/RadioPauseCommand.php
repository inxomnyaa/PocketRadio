<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;
use xenialdan\PocketRadio\playlist\Playlist;

class RadioPauseCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio.pause");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		Loader::$serverPlaylist->getState() === Playlist::STATE_PLAYING ? Loader::$serverPlaylist->pause() : Loader::$serverPlaylist->play(Playlist::PLAYLIST_UNPAUSED);
		$sender->sendMessage(TextFormat::GREEN . "Radio " . (Loader::$serverPlaylist->getState() === Playlist::STATE_PLAYING ? "playing" : "paused") . "!");
	}
}
