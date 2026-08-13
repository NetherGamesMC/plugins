<?php

declare(strict_types=1);

namespace skywars\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use skywars\Skywars;

class SWPlayer extends Human
{
    /** @var string */
    private string $animation;
    /** @var bool */
    private bool $send = false;

    public function __construct(Player $player)
    {
        parent::__construct($player->getLocation(), $player->getSkin());

        $this->animation = 'animation.ng.corpse.belly1';

        $this->setCanSaveWithChunk(false);
    }

    public function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0, 0, 0);
    }

    public function playAnimation(): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::IDLING, false);

        $this->getWorld()->broadcastPacketToViewers($this->getPosition()->asVector3(), AnimateEntityPacket::create(
            $this->animation,
            "",
            "",
            0,
            "",
            0,
            [$this->getId()]
        ));
        $this->send = true;
    }

    public function sendSpawnPacket(Player $player): void
    {
        if ($this->send) {
            $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::IDLING, true);

            parent::sendSpawnPacket($player);

            Skywars::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                if ($player->isConnected()) {
                    $player->getNetworkSession()->sendDataPacket(AnimateEntityPacket::create(
                        $this->animation . '.end',
                        "",
                        "",
                        0,
                        "",
                        0,
                        [$this->getId()]
                    ));
                }
            }), 5);
        } else {
            parent::sendSpawnPacket($player);
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();
    }
}