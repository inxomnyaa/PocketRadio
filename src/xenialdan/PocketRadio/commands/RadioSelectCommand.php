<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;

class RadioSelectCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio.select");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		$title = TextFormat::BLUE . TextFormat::BOLD . Loader::getInstance()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Select a song (".count(Loader::$songlist)." songs)";
		$form = (new SimpleForm(static function(Player $player, $data) : void{
			if($data === null){
				return;
			}
			$song = Loader::$songlist[$data];
			$player->sendMessage(TextFormat::GREEN . "Playing " . $song->getSongTitleAndAuthorInfo());
			Loader::playNext($song);
		}))
			->setTitle($title);
		foreach(Loader::$songlist as $songName => $song){//TODO cache the list as it lags the whole server. Maybe split into pages
			$form->addButton($song->getTitleOrFilename(), label: $songName);
		}
		$sender->sendForm($form);
	}
}
