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
 * @author Drew, Driesboy
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\passive;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\Bowl;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function mt_rand;

class Mooshroom extends Cow
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::MOOSHROOM;
    }

    public function getName(): string
    {
        return 'Mooshroom';
    }

    public function isHarvestableWith(Item $item): bool
    {
        return $item instanceof Bowl;
    }

    public function getHarvestItem(): Item
    {
        return VanillaItems::MUSHROOM_STEW();
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaBlocks::RED_MUSHROOM()->asItem()->setCount(mt_rand(0, 2)),
            VanillaItems::LEATHER()->setCount(mt_rand(0, 2)),
            ($this->isOnFire() ? VanillaItems::STEAK() : VanillaItems::RAW_BEEF())->setCount(mt_rand(1, 3))
        ];
    }
}
