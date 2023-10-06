<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use CortexPE\Commando\BaseSubCommand;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;

class RadioVolumeCommand extends BaseSubCommand{
	protected function prepare() : void{
		$this->setPermission("pocketradio.command.radio.volume");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
			return;
		}
		$volume = Loader::getVolume($sender);
		$title = TextFormat::BLUE . TextFormat::BOLD . Loader::getInstance()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
		$form = (new CustomForm(function(Player $player, $data) : void{
			if($data === null){
				$player->sendMessage(TextFormat::RED . "Volume unchanged, current volume is " . Loader::getInstance()->getVolume($player) . "%");
				return;
			}
			$player->sendMessage(TextFormat::GREEN . "Volume changed to " . $data["volume"] . "%");
			Loader::setVolume($player, (int) $data["volume"]);
		}))
			->addSlider("Volume", 0, 100, 10, default: $volume, label: "volume")
			->setTitle($title);
		$sender->sendForm($form);
	}
}
