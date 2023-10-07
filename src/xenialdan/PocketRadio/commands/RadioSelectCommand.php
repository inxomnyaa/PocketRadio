<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;
use xenialdan\PocketRadio\playlist\Playlist;

class RadioSelectCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio.select");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		$title = TextFormat::BLUE . TextFormat::BOLD . Loader::getInstance()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Select a song (" . Loader::$serverPlaylist->getCount() . " songs)";
		$form = (new SimpleForm(static function(Player $player, $data) : void{
			if($data === null){
				return;
			}
			Loader::$serverPlaylist->stop(Playlist::PLAYLIST_SELECTED_SONG);
			if(Loader::$serverPlaylist->seek($data)){
				Loader::$serverPlaylist->play(Playlist::PLAYLIST_SELECTED_SONG);
				$player->sendMessage(TextFormat::GREEN . "Playing " . Loader::$serverPlaylist->getCurrent()->getSongTitleAndAuthorInfo());
			}else{
				$player->sendMessage(TextFormat::RED . "Song not found");
			}
		}))
			->setTitle($title);
		foreach(Loader::$serverPlaylist->getSongs() as $songName => $song){//TODO cache the list as it lags the whole server. Maybe split into pages
			$form->addButton($song->getTitleOrFilename(), label: $songName);
		}
		$sender->sendForm($form);
	}
}
