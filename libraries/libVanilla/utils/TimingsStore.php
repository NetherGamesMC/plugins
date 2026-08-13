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

namespace libVanilla\utils;

use pocketmine\timings\TimingsHandler;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;

final class TimingsStore
{
    use SingletonTrait;

    /** @var array<string, TimingsHandler> */
    private array $store = [];

    public function get(string $id, ?string $displayName = null, ?TimingsHandler $parent = null): TimingsHandler
    {
        return $this->store[$id] ??= new TimingsHandler($displayName ?? $id, $parent);
    }

    public function getWithParent(string $parentId, ?string $displayName): TimingsHandler
    {
        return $this->get(
            "$parentId - $displayName",
            null, $this->get($parentId)
        );
    }

    public static function shortName(object $obj): string
    {
        return (new ReflectionClass($obj))->getShortName();
    }
}