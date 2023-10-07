<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;
use xenialdan\PocketRadio\playlist\Playlist;

class RadioNextCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio.next");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		Loader::$serverPlaylist->stop(Playlist::PLAYLIST_NEXT_SONG);
		$song = Loader::$serverPlaylist->getNext();
		if($song === null){
			$sender->sendMessage(TextFormat::RED . "No song playing (playlist queue is empty or at the end)");
		}else{
			Loader::$serverPlaylist->play(Playlist::PLAYLIST_NEXT_SONG);
			$sender->sendMessage(TextFormat::GREEN . "Playing next song: " . $song->getSongTitleAndAuthorInfo());
		}
	}
}
