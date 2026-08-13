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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\network\types;

use pocketmine\network\mcpe\protocol\types\DimensionIds;

enum DimensionType
{
    case OVERWORLD;
    case NETHER;
    case END;

    public function getId(): int
    {
        return match ($this) {
            self::OVERWORLD => DimensionIds::OVERWORLD,
            self::NETHER => DimensionIds::NETHER,
            self::END => DimensionIds::THE_END,
        };
    }

    public function getFogType(): string
    {
        return match ($this) {
            self::OVERWORLD => "minecraft:fog_default",
            self::NETHER => "minecraft:fog_hell",
            self::END => "minecraft:fog_the_end",
        };
    }
}