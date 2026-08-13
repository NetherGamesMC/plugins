<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */

namespace conquests\shops;

final class UpgradeTier
{
    public function __construct(
        public readonly string $description,
        public readonly int    $cost,
        public readonly int    $teamCost,
        public readonly string $customName = "",
        public readonly string $tieredText = ""
    )
    {
    }


    public function hasCustomName(): bool
    {
        return $this->customName !== "";
    }

    public function hasTieredText(): bool
    {
        return $this->tieredText !== "";
    }
}