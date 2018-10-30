<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\commands;

use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;

abstract class Command extends PluginCommand
{

    public function __construct($name, Plugin $owner)
    {
        parent::__construct($name, $owner);
        $this->setPermission("pocketradio.command");
    }
}
