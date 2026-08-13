<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\entity\pets\bouncing;

use InvalidArgumentException;
use libVanilla\entity\Monster;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\world\sound\Sound;

abstract class CubePet extends Monster implements IPetEntity
{
    // todo: rider position: Vector3(0, 0.78 + $this->scale * 0.9, -0.25) // derive this from height??
    use BouncingPetTrait {
        BouncingPetTrait::jump as private traitJump;
    }

    private int $slimeSize = 0; // 0, 1, 3 naturally spawn (not 2)

    public function getSlimeSize(): int
    {
        return $this->slimeSize;
    }

    public function setSlimeSize(int $slimeSize): void
    {
        if ($slimeSize < 0 || $slimeSize > 126) {
            throw new InvalidArgumentException("Only sizes between 0-126 are acceptable.");
        }
        $this->slimeSize = $slimeSize;
    }

    public function isSmall(): bool
    {
        return $this->slimeSize < 1;
    }

    public function jump(): void
    {
        if ($this->onGround) {
            $this->broadcastSound($this->newBounceSound());
        }
        $this->traitJump();
    }

    abstract protected function newBounceSound(): Sound;

    public function getRiderSeatPosition(Entity $rider): Vector3
    {
        return new Vector3(0, 2, 0);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        $size = 0.51 * ($this->slimeSize + 1);
        return new EntitySizeInfo($size, $size);
    }

    protected function onHitGround(): ?float
    {
        $this->broadcastSound($this->newBounceSound());
        return parent::onHitGround();
    }
}