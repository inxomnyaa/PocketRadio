<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\customui\elements\Button;
use xenialdan\customui\elements\Dropdown;
use xenialdan\customui\elements\Slider;
use xenialdan\customui\windows\CustomForm;
use xenialdan\customui\windows\SimpleForm;
use xenialdan\PocketRadio\Loader;

class RadioCommand extends Command
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct("radio", $plugin);
        $this->setPermission("pocketradio.command.radio");
        $this->setDescription("Manage radio");
        $this->setUsage("/radio | /radio volume | /radio select");
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
            $return = true;
            switch ($args[0] ?? "menu") {
                case "menu":
                    {
                        $volume = Loader::getVolume($sender);
                        if ($volume === false) {
                            $sender->sendMessage(TextFormat::RED . "Error accessing volume config");
                            return false;
                        }
                        $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Change your volume";
                        $form = new SimpleForm($title);
                        $form->addButton(new Button("Next"));
                        $form->addButton(new Button("Pause"));
                        $form->addButton(new Button("Previous"));
                        $form->addButton(new Button("Volume"));
                        $form->addButton(new Button("Select song"));
                        $form->setCallable(function (Player $player, $data) {
                            switch ($data) {
                                case "Next":
                                    {
                                        Loader::playNext();
                                        break;
                                    }
                                case "Pause":
                                    {
                                        Loader::getInstance()->getScheduler()->cancelAllTasks();
                                        break;
                                    }
                                case "Previous":
                                    {
                                        $player->sendMessage("Under todo, can not play previous yet");
                                        break;
                                    }
                                case "Volume":
                                    {
                                        $player->getServer()->dispatchCommand($player, "radio volume");
                                        break;
                                    }
                                case "Select song":
                                    {
                                        $player->getServer()->dispatchCommand($player, "radio select");
                                        break;
                                    }
                            }
                        });
                        $sender->sendForm($form);
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
                            $dropdown = new Slider("Volume", 0, 100, 10.0);
                            $dropdown->setDefaultValue($volume);
                            $form->addElement($dropdown);
                        } catch (\Exception $e) {
                        }
                        $form->setCallable(function (Player $player, $data) {
                            Loader::setVolume($player, $data[0]);
                        });
                        $sender->sendForm($form);
                        break;
                    }
                case "select":
                    {
                        $title = TextFormat::BLUE . TextFormat::BOLD . $this->getPlugin()->getDescription()->getPrefix() . " " . TextFormat::RESET . TextFormat::DARK_BLUE . "Select a song";
                        $form = new CustomForm($title);
                        $dropdown = new Dropdown("Song", []);
                        foreach (Loader::$songlist as $i => $song) {
                            $song = basename($song, ".nbs");
                            $dropdown->addOption($song, $i === 0);
                        }
                        $form->addElement($dropdown);
                        $form->setCallable(function (Player $player, $data) use ($form) {
                            if (empty($data[0]) || $data[0] === "") {
                                $player->sendForm($form);
                                return;
                            }
                            Loader::addToPlaylist($data[0] . ".nbs");
                            Loader::playNext();
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
