<?php

namespace xenialdan\PocketRadio;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\Plugin;

class EventListener implements Listener
{
    /** @var Plugin */
    public $owner;

    public function __construct(Plugin $plugin)
    {
        $this->owner = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        if (empty(Loader::$tasks)) {
            $this->owner->startTask();
        }
    }
}