<?php

namespace xenialdan\PocketRadio;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use xenialdan\PocketRadio\event\LoadSongsEvent;
use xenialdan\PocketRadio\playlist\Playlist;
use function count;

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

	public function onLoadSongs(LoadSongsEvent $event) : void{
		if($event->getPlaylistName() === Loader::SERVER_PLAYLIST_NAME){
			Loader::getInstance()->getLogger()->info($event->getPlaylistName() . ": Loaded " . count($event->getSongs()) . " songs");
			foreach($event->getErrors() as $error){
				Loader::getInstance()->getLogger()->error($error);
			}
			Loader::$serverPlaylist->addSongs(...$event->getSongs());
		}
	}
}