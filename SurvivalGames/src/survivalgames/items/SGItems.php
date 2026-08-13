<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author xBeastMode
 *
 */
declare(strict_types=1);

namespace survivalgames\items;

use libVanilla\item\FishingRod;
use libVanilla\LibVanillaItems;
use pocketmine\item\Item;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static FishingRod GRAPPLING_ROD()
 */
final class SGItems
{
    use CloningRegistryTrait;

    private function __construct()
    {
    }

    /**
     * @return Item[]
     */
    public static function getAll(): array
    {
        /** @var Item[] $result */
        $result = self::_registryGetAll();
        return $result;
    }

    protected static function setup(): void
    {
        $grapplingHook = LibVanillaItems::FISHING_ROD()->setCustomName('§r§l§gGrappling Rod');
        $grapplingHook->getNamedTag()->setByte('grappling_rod', 1);

        self::register("grappling_rod", $grapplingHook);
    }

    protected static function register(string $name, Item $item): void
    {
        self::_registryRegister($name, $item);
    }
}