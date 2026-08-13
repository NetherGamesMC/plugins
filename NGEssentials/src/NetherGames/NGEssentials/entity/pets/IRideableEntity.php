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
use pocketmine\math\Vector3;

interface IRideableEntity
{
    public function addRider(Entity $rider): void;

    public function isRiddenBy(Entity $rider): bool;

    public function hasRider(): bool;

    public function removeRider(Entity $rider): void;

    public function getRiderSeatPosition(Entity $rider): Vector3;

    /**
     * @param float $WS +1 for Forward, -1 for Backward
     * @param float $AD +1 for Right, -1 for Left
     */
    public function onRiderControl(Entity $rider, float $WS, float $AD): void;
}