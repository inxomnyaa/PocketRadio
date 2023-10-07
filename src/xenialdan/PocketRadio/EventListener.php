<?php

namespace xenialdan\PocketRadio;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use xenialdan\PocketRadio\playlist\Playlist;

class EventListener implements Listener{

	public function onJoin(PlayerJoinEvent $event) : void{
		Loader::$serverPlaylist->subscribe($event->getPlayer());
		if(Loader::$serverPlaylist->task === null){
			Loader::$serverPlaylist->play(Playlist::PLAYLIST_PLAYER_ADDED);
		}
	}

	public function onLeave(PlayerQuitEvent $event) : void{
		Loader::$serverPlaylist->unsubscribe($event->getPlayer());
		if(count(Loader::$serverPlaylist->getPlayers()) === 0){
			Loader::$serverPlaylist->stop(Playlist::PLAYLIST_NO_PLAYERS);
		}
	}
}