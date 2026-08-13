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
declare(strict_types=1);

namespace conquests\generators;

use conquests\CQTeam;
use conquests\shops\Upgrade;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;

class TeamGenerator extends Generator
{
    /** @var CQTeam */
    private CQTeam $team;

    private bool $enableIron;
    private bool $enableGold;
    private bool $enableEmerald;

    public function __construct(Location $location, CQTeam $team)
    {
        $this->team = $team;

        $enabledGenerators = $team->getArena()->getGameSettings()->getEnabledGenerators();
        $this->enableIron = in_array(GeneratorEnum::IRON, $enabledGenerators);
        $this->enableGold = in_array(GeneratorEnum::GOLD, $enabledGenerators);
        $this->enableEmerald = in_array(GeneratorEnum::EMERALD, $enabledGenerators);

        parent::__construct($location, AxisAlignedBB::one()->expand(2, 2, 2)->offset($location->getX(), $location->getY(), $location->getZ()));
    }

    public function tick(): void
    {
        if ($this->enableIron) {
            if ($this->time % $this->getSpawnTime(ItemTypeIds::IRON_INGOT) === 0) {
                $this->dropItem(VanillaItems::IRON_INGOT(), true);
            }
        }

        if ($this->enableGold) {
            if ($this->time % $this->getSpawnTime(ItemTypeIds::GOLD_INGOT) === 0) {
                $this->dropItem(VanillaItems::GOLD_INGOT(), true);
            }
        }

        if ($this->enableEmerald) {
            $emeraldSpawnTime = $this->getSpawnTime(ItemTypeIds::EMERALD);
            if ($emeraldSpawnTime !== 0 && $this->time % $emeraldSpawnTime === 0) {
                $this->dropItem(VanillaItems::EMERALD(), true);
            }
        }

        $this->time++;
    }

    public function getSpawnTime(int $id): int
    {
        $level = $this->team->getUpgradeLevel(Upgrade::FORGE());

        return match ($id) {
            ItemTypeIds::GOLD_INGOT => match ($level) {
                1 => 45,
                2, 3 => 24,
                4 => 12,
                default => 52,
            },
            ItemTypeIds::EMERALD => match ($level) {
                3 => 500,
                4 => 200,
                default => 0,
            },
            default => $level === 0 ? 13 : 7,
        };
    }

    public function maxCapacity(Item $item): int
    {
        return match ($item->getTypeId()) {
            ItemTypeIds::IRON_INGOT => 48,
            ItemTypeIds::EMERALD => 8,
            default => 15,
        };
    }
}