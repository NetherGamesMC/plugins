<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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

namespace libVanilla\entity\utils;

use pocketmine\entity\EntitySizeInfo;

final class EntitySizeUtils
{
    private function __construct()
    {
    }

    public static function upright(float $height, float $width): EntitySizeInfo
    {
        // 1.62 (human eye height) / 1.8 (human height) = 0.9,
        // therefore eye height is 90% of height for humanoid-type entities
        // or basically, upright standing entities
        return new EntitySizeInfo($height, $width, $height * 0.9);
    }
}