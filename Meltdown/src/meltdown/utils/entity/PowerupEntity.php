<?php

namespace meltdown\utils\entity;

use meltdown\arena\MDArena;
use meltdown\utils\StatsData;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\player\Player;

abstract class PowerupEntity extends Entity{
    public const START_ANIMATION_TICK = 60;

    private int $bobbingTicks = 0;

    public function __construct(Location $location, private MDArena $arena){
        parent::__construct($location);

        $this->setNameTagAlwaysVisible();
        $this->setCanSaveWithChunk(false);
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.5, 0.5);
    }

    protected function getInitialDragMultiplier() : float{
        return 0.0;
    }

    protected function checkBlockIntersections() : void{

    }

    public function onCollideWithPlayer(Player $player) : void{
        $this->flagForDespawn();

        $statsData = $this->arena->getStatsData();
        $statsData->addValue($player, StatsData::MD_POWERUPS_COLLECTED);
    }

    abstract protected function getAnimationId() : string;

    protected function entityBaseTick(int $tickDiff = 1) : bool{
        parent::entityBaseTick($tickDiff);

        if($this->bobbingTicks % self::START_ANIMATION_TICK === 0){
            $this->bobbingTicks = 1;

            NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [AnimateEntityPacket::create(
                $this->getAnimationId(),
                "",
                "",
                0,
                "",
                0,
                [$this->getId()]
            )]);
        }else{
            $this->bobbingTicks += $tickDiff;
        }

        return true;
    }

    public function setMotion(Vector3 $motion) : bool{
        return false;
    }

    public function canBeMovedByCurrents() : bool{
        return false;
    }

    protected function getInitialGravity() : float{
        return 0.0;
    }

    public function canBeCollidedWith() : bool{
        return false;
    }
}