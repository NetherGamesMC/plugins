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


namespace NetherGames\NGEssentials\entity\pets;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

interface IPetEntity extends IRideableEntity
{
    public function __construct(Location $location, Entity $owningEntity, ?CompoundTag $nbt = null);

    public static function getNetworkTypeId(): string;

    public function getOwningEntityInWorld(): ?Entity;

    public function getName(): string;

    public function lerpTeleport(Vector3 $pos): void;

    public function moveTo(Vector3 $pos): void;

    public function stopMoving(): void;

    public function tryLookAtOwner(): void;

    public function refreshFollowOffset(): Vector3;

    public function getFollowOffset(): Vector3;

    public function getSafeLocation(Vector3 $reference): Vector3;

    public function petEntityBaseTick(int $tickDiff): bool;
}