<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\task;

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xenialdan\libnbs\Layer;
use xenialdan\libnbs\NBSFile;
use xenialdan\libnbs\Song;
use xenialdan\PocketRadio\Loader;

class SongPlayerTask extends Task
{
    public $song = null;
    public $songfilename = "";
    public $currentLength = 0;
    /** @var Plugin|Loader */
    public $owner;
    /** @var int */
    public $lastTick = -1;
    public $lastPlayed;
    /** @var bool */
    protected $playing = false;
    /** @var int */
    private $tick = -1;
    /** @var float */
    private $startTime;

    public function __construct(Plugin $owner, string $songfilename, Song $song)
    {
        print $song . PHP_EOL;
        $this->owner = $owner;
        $this->song = $song;
        $this->songfilename = $songfilename;
        Loader::$tasks[] = $this->getTaskId();
        $this->playing = true;
        $owner->getServer()->broadcastMessage(TextFormat::GREEN . $this->owner->getDescription()->getPrefix() . " Now playing: " . (empty($this->song->getTitle()) ? basename($songfilename, ".nbs") : $this->song->getTitle()) . (empty($this->song->getAuthor()) ? "" : " by " . $this->song->getAuthor()));
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick)
    {
        #if(!$this->startTime){
        #    $this->startTime = microtime(true);
        #}
        if (!$this->playing) {
            //$this->startTime = microtime(true) - (microtime(true) - $this->startTime);
            return;
        }
        if ($this->tick > $this->song->getLength()) {
            $this->tick = -1;
            $this->playing = false;
            Loader::getInstance()::playNext();
            return;
        }
        #$tick = $this->tick + 1;
        #$delayMillis = 1000/$this->song->getSpeed();
        #$duration = round((microtime(true) - $this->startTime)*1000,0);
        #$ctick = floor($duration);
        //print "t{$tick}\t:d$duration\t:x".($duration/$delayMillis)."\t:s".($duration%$delayMillis)."\t:ctick".($ctick)."\t".PHP_EOL;
        #if (($duration%$delayMillis) < 1000/20) {
        $this->tick++;
        #    print "♪".$this->tick." ".$duration." ".($duration%$delayMillis);
        foreach ($this->owner->getServer()->getOnlinePlayers() as $player)
            $this->playTick($player, $this->tick);
        #}
    }

    public function playTick(Player $player, int $tick): void
    {
        $playerVolume = Loader::getSoundVolume($player);

        /** @var Layer $layer */
        foreach ($this->song->getLayerHashMap()->values()->toArray() as $layer) {
            $note = $layer->getNote($tick);
            if ($note === null) {
                continue;
            }

            $volume = ($layer->getVolume() * (int)$playerVolume) / 100;//TODO add song volume
            //shift nbs range for note block sounds (33 - 57) to start at 0
            //then shift by some extra -12 for the
            //pitch calculation: https://minecraft.gamepedia.com/Note_Block#Notes
            $pitch = 2 ** (($note->getKey() - 45) / 12);
            $sound = NBSFile::MAPPING[$note->instrument] ?? NBSFile::MAPPING[NBSFile::INSTRUMENT_PIANO];
            $pk = new PlaySoundPacket();
            //TODO custom sound support, figure out path in resource pack
            $pk->soundName = $sound;
            $pk->pitch = $pitch;
            $pk->volume = $volume;
            $vector = $player->asVector3();
            if ($layer->stereo !== 100) {//Not centered, modify position. TODO check
                $yaw = ($player->yaw - 90) % 360;
                $add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();
                $multiplier = 2 * ($layer->stereo - 100) / 100;
                $add = $add->multiply($multiplier);
                $vector->add($add->x, 0, $add->y);
            }
            $pk->x = $vector->x;
            $pk->y = $vector->y + $player->getEyeHeight();
            $pk->z = $vector->z;
            $player->dataPacket($pk);
            unset($add, $pk, $vector, $note);
        }
    }
}