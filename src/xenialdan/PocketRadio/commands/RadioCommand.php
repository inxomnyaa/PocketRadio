<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\form\CustomForm;
use pocketmine\form\element\Slider;
use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\PocketRadio\Loader;

class RadioCommand extends Command{
	public function __construct(Plugin $plugin){
		parent::__construct("radio", $plugin);
		$this->setPermission("pocketradio.command.radio");
		$this->setDescription("Manage radio");
		$this->setUsage("/radio <next>");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var Player $sender */
		$return = $sender->hasPermission($this->getPermission());
		if (!$return){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));
			return true;
		}
		try{
			if (empty($args)) throw new \InvalidArgumentCountException("Too less arguments supplied");
			//TODO
			$return = true;
			switch ($args[0]){
				case "next": {
					Loader::getInstance()->getServer()->getScheduler()->cancelTasks(Loader::getInstance());
					Loader::getInstance()->startTask();
					break;
				}
				case "volume": {
					$volume = Loader::getVolume($sender);
					if ($volume === false){
						$sender->sendMessage(TextFormat::RED . "Error accessing volume config");
						$return = false;
						break;
					}
					$title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
					$sender->sendForm(new class($title, [new Slider("Volume", 0, 100, 10.0, $volume)]) extends CustomForm{
						public function onSubmit(Player $player): ?Form{
							Loader::setVolume($player, $this->getElement(0)->getValue());
							return null;
						}
					});
					break;
				}
				default: {
					throw new \InvalidArgumentException("Unknown argument supplied: " . $args[0]);
				}
			}
		} catch (\Error $error){
			$this->getPlugin()->getLogger()->error($error->getMessage());
			$this->getPlugin()->getLogger()->error($error->getLine());
			$return = false;
		} finally{
			return $return;
		}
	}
}
