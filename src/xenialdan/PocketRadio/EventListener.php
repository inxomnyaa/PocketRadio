<?php

namespace xenialdan\PocketRadio;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener
{
    /** @var Loader */
    public $owner;

    public function __construct(Loader $plugin)
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