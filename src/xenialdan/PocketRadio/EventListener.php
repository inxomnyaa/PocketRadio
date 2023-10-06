<?php

namespace xenialdan\PocketRadio;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

class EventListener implements Listener{

	public function onJoin(PlayerJoinEvent $event) : void{
		if(Loader::$task === null){
			Loader::getInstance()->startTask();
		}
	}

	public function onLeave(PlayerQuitEvent $event) : void{
		if(count(Server::getInstance()->getOnlinePlayers()) === 0){
			Loader::getInstance()->stopTask();
		}
	}
}