<?php

declare(strict_types=1);

namespace xenialdan\PocketRadio\task;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
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
    /** @var Plugin|Loader */
    public $owner;
    /** @var bool */
    protected $playing = false;
    /** @var int */
    private $tick = -1;

    public function __construct(Plugin $owner, string $songfilename, Song $song)
    {
        $this->owner = $owner;
        $this->song = $song;
        $this->songfilename = $songfilename;
        Loader::$tasks[] = $this;
        $this->playing = true;
        $owner->getServer()->broadcastMessage(TextFormat::GREEN . $this->owner->getDescription()->getPrefix() . " Now playing: " . (empty($this->song->getTitle()) ? basename($songfilename, ".nbs") : $this->song->getTitle()) . (empty($this->song->getAuthor()) ? "" : " by " . $this->song->getAuthor()));
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(): void
    {
        if (!$this->playing) {
            return;
        }
        if ($this->tick > $this->song->getLength()) {
            $this->tick = -1;
            $this->playing = false;
            Loader::getInstance()::playNext();
            return;
        }
        $this->tick++;
        foreach ($this->owner->getServer()->getOnlinePlayers() as $player)
            $this->playTick($player, $this->tick);
    }

    public function playTick(Player $player, int $tick): void
    {
        $playerVolume = Loader::getSoundVolume($player);

        /** @var Layer $layer */
        foreach ($this->song->getLayerHashMap() as $layer) {
            $note = $layer->getNote($tick);
            if ($note === null) {
                continue;
            }

            $volume = (($layer->getVolume() * Loader::getVolume($player)) / 10000);
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
            $vector = $player->getLocation()->asVector3();
            /*if ($layer->stereo !== 100) {//Not centered, modify position. TODO fix
                $yaw = ($player->yaw - 90) % 360;
                $add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();
                $multiplier = 2 * (($layer->stereo - 100) / 100);
                $add = $add->multiply($multiplier);
                $vector->add($add->x, 0, $add->y);
            }*/
            $pk->x = $vector->x;
            $pk->y = $vector->y + $player->getEyeHeight();
            $pk->z = $vector->z;
            $player->getNetworkSession()->sendDataPacket($pk);
            unset($add, $pk, $vector, $note);
        }
    }
}
