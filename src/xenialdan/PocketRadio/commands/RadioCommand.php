<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\customui\elements\Slider;
use xenialdan\customui\windows\CustomForm;
use xenialdan\PocketRadio\Loader;

class RadioCommand extends Command
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct("radio", $plugin);
        $this->setPermission("pocketradio.command.radio");
        $this->setDescription("Manage radio");
        $this->setUsage("/radio <next>");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $sender */
        $return = $sender->hasPermission($this->getPermission());
        if (!$return) {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
            return true;
        }
        try {
            if (empty($args)) throw new \InvalidArgumentCountException("Too less arguments supplied");
            //TODO
            $return = true;
            switch ($args[0]) {
                case "next":
                    {
                        Loader::getInstance()->getScheduler()->cancelAllTasks();
                        Loader::getInstance()->startTask();
                        break;
                    }
                case "volume":
                    {
                        $volume = Loader::getVolume($sender);
                        if ($volume === false) {
                            $sender->sendMessage(TextFormat::RED . "Error accessing volume config");
                            $return = false;
                            break;
                        }
                        $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
                        $form = new CustomForm($title);
                        try {
                            $slider = new Slider("Volume", 0, 100, 10.0);
                            $slider->setDefaultValue($volume);
                            $form->addElement($slider);
                        } catch (\Exception $e) {
                        }
                        $form->setCallable(function (Player $player, $data) {
                            Loader::setVolume($player, $data[0]);
                        });
                        $sender->sendForm($form);
                        break;
                    }
                default:
                    {
                        throw new \InvalidArgumentException("Unknown argument supplied: " . $args[0]);
                    }
            }
        } catch (\Error $error) {
            $this->getPlugin()->getLogger()->error($error->getMessage());
            $this->getPlugin()->getLogger()->error($error->getLine());
            $return = false;
        } finally {
            return $return;
        }
    }
}
