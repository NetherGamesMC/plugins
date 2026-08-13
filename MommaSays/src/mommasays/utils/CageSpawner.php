<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */

namespace mommasays\utils;

use mommasays\MSArena;
use mommasays\utils\entity\collector\CageActionCollector;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class CageSpawner
{
    /** @var MSArena */
    private MSArena $arena;
    /** @var CageActionCollector */
    private CageActionCollector $collector;

    public function __construct(MSArena $arena)
    {
        $this->arena = $arena;
    }

    public function buildCage(Vector3 $origin, World $world, DyeColor $color): void
    {
        $this->collector = CageActionCollector::create($world);

        $this->buildBaseX($origin, $color);
        $this->buildBaseX($origin->add(0, 0, 7), $color);
        $this->buildBaseZ($origin, $color);
        $this->buildBaseZ($origin->add(7, 0, 0), $color);

        $stainedGlass = VanillaBlocks::STAINED_GLASS()->setColor($color);
        $stainedClay = VanillaBlocks::STAINED_CLAY()->setColor($color);

        $this->spawnRect($stainedGlass, $origin, 6);
        $this->spawnRect($stainedClay, $origin->add(1, 0, 1), 4);
        $this->spawnRect($stainedGlass, $origin->add(2, 0, 2), 2);

        $this->collector->execute();

        if ($color === DyeColor::GREEN) {
            // This is the Winner Cage
            $this->arena->setWinnerSpawn($origin->add(4, 1, 4));
        } elseif ($color === DyeColor::RED) {
            // This is the Loser Cage
            $this->arena->setLoserSpawn($origin->add(4, 1, 4));
        }
    }

    private function buildBaseX(Vector3 $origin, DyeColor $color): void
    {
        $stainedClay = VanillaBlocks::STAINED_CLAY()->setColor($color);

        for ($x = 1; $x < 7; $x++) {
            $this->collector->add($origin->add($x, 1, 0), VanillaBlocks::GLASS());
            $this->collector->add($origin->add($x, 2, 0), VanillaBlocks::GLASS());
            $this->collector->add($origin->add($x, 3, 0), VanillaBlocks::GLASS());
            $this->collector->add($origin->add($x, 4, 0), $stainedClay);
        }
    }

    private function buildBaseZ(Vector3 $origin, DyeColor $color): void
    {
        $stainedClay = VanillaBlocks::STAINED_CLAY()->setColor($color);

        for ($z = 1; $z < 7; $z++) {
            $this->collector->add($origin->add(0, 1, $z), VanillaBlocks::GLASS());
            $this->collector->add($origin->add(0, 2, $z), VanillaBlocks::GLASS());
            $this->collector->add($origin->add(0, 3, $z), VanillaBlocks::GLASS());
            $this->collector->add($origin->add(0, 4, $z), $stainedClay);
        }
    }

    private function spawnRect(Block $block, Vector3 $origin, int $size): void
    {
        $this->spawnQ($block, $origin->add(1, 0, 1), $size);
        $this->spawnQInverted($block, $origin->add($size, 0, $size), $size);
    }

    private function spawnQ(Block $block, Vector3 $vector3, int $size): void
    {
        for ($x = 0; $x < $size; $x++) {
            for ($z = 0; $z < $size; $z++) {
                if ($x === 0 || $z === 0) {
                    $this->collector->add($vector3->add($x, 0, $z), $block);
                }
            }
        }
    }

    private function spawnQInverted(Block $block, Vector3 $vector3, int $size): void
    {
        for ($x = 0; $x < $size; $x++) {
            for ($z = 0; $z < $size; $z++) {
                if ($x === 0 || $z === 0) {
                    $this->collector->add($vector3->subtract($x, 0, $z), $block);
                }
            }
        }
    }
}