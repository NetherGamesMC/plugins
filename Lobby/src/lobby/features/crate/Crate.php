<?php

declare(strict_types=1);

namespace lobby\features\crate;

use lobby\entity\custom\CrateEntity;
use lobby\Lobby;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Crate
{
    /** @var int */
    private int $floatingText;
    /** @var Player|null */
    private ?Player $inUse = null;

    public function __construct(private CratesHandler $handler, private Vector3 $position)
    {
        $floatingText = new FloatingText(new Location($position->getX(), $position->getY() + 1.5, $position->getZ(), $this->handler->getPlugin()->getServer()->getWorldManager()->getDefaultWorld(), 0.0, 0.0), TextFormat::GREEN . "Click to open a crate!");
        $this->handler->getPlugin()->getEntityManager()->addEntity($floatingText);
        $this->floatingText = $floatingText->getId();
        $location = Location::fromObject($this->position, Lobby::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 180);

        $entity = new CrateEntity($location);
        $location->getWorld()->registerChunkLoader($entity, $location->getX() >> 4, $location->getZ() >> 4);
    }

    public function getPlayer(): ?Player
    {
        return $this->inUse;
    }

    public function isInUse(): bool
    {
        return $this->inUse !== null;
    }

    public function setInUse(?Player $player): void
    {
        $this->inUse = $player;
    }

    public function getFloatingText(): FloatingText
    {
        /** @var FloatingText $floatingText */
        $floatingText = $this->getCratesHandler()->getPlugin()->getEntityManager()->getEntity($this->getCratesHandler()->getPlugin()->getServer()->getWorldManager()->getDefaultWorld(), $this->floatingText);

        return $floatingText;
    }

    public function getCratesHandler(): CratesHandler
    {
        return $this->handler;
    }

    public function asVector3(): Vector3
    {
        return $this->position;
    }
}