<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;

class RadioCommand extends BaseCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio");
		$this->registerSubCommand(new RadioVolumeCommand(Loader::getInstance(), "volume"));
		$this->registerSubCommand(new RadioSelectCommand(Loader::getInstance(), "select"));
		$this->registerSubCommand(new RadioNextCommand(Loader::getInstance(), "next"));
		$this->registerSubCommand(new RadioPauseCommand(Loader::getInstance(), "pause"));
	}


	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		$title = TextFormat::BLUE . TextFormat::BOLD . Loader::getInstance()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Controll the radio";
		$form = (new SimpleForm(function(Player $player, $data) : void{
			switch($data){
				case "next":
				{
					$player->getServer()->dispatchCommand($player, "radio next");
					break;
				}
				case "pause":
				{
					$player->getServer()->dispatchCommand($player, "radio pause");
					break;
				}
				case "previous":
				{
					$player->sendMessage("Under todo, can not play previous yet");
					break;
				}
				case "volume":
				{
					$player->getServer()->dispatchCommand($player, "radio volume");
					break;
				}
				case "select":
				{
					$player->getServer()->dispatchCommand($player, "radio select");
					break;
				}
				case null://closed UI
				{
					break;
				}
				default:
				{
					$player->sendMessage("Invalid command");
					break;
				}
			}
		}))
			->setTitle($title);
		if($sender->hasPermission("pocketradio.command.radio.next")) $form->addButton("Next", label: "next");//TODO display next song
		if($sender->hasPermission("pocketradio.command.radio.pause")) $form->addButton("Pause", label: "pause");
		if($sender->hasPermission("pocketradio.command.radio.volume")) $form->addButton("Volume (" . Loader::getVolume($sender) . "%%)", label: "volume");
		if($sender->hasPermission("pocketradio.command.radio.select")) $form->addButton("Select song (" . count(Loader::$songlist) . ")", label: "select");
		if(Loader::$task !== null && Loader::getCurrentSong() !== null){
			$form->setContent("Currently playing: " . Loader::getCurrentSong()->getSongTitleAndAuthorInfo());
		}else{
			$form->setContent("No song playing");
		}
		$sender->sendForm($form);
	}
}
