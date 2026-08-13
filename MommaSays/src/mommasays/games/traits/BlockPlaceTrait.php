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

namespace mommasays\games\traits;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use mommasays\utils\data\BlockSets;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\StainedHardenedClay;
use pocketmine\block\utils\ColoredTrait;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\World;

trait BlockPlaceTrait
{
    public function replaceSingle(World $world, Block $block): void
    {
        $selection = new Selection();

        $xMin = -11;
        $xMax = 13;
        $zMin = -13;
        $zMax = 13;

        for ($x = $xMin; $x < $xMax; ++$x) {
            for ($z = $zMin; $z < $zMax; ++$z) {
                if ($world->getBlock(new Vector3($x, 49, $z))->getTypeId() !== BlockTypeIds::AIR) {
                    $selection->add($x, 49, $z, $block);
                }
            }
        }

        AsyncBlockManager::executeSet($selection, $world);
    }

    /**
     * @param DyeColor[] $dyeColors
     */
    public function replaceMultiple(World $world, array $dyeColors): void
    {
        $selection = new Selection();

        $xMin = -11;
        $xMax = 13;
        $zMin = -13;
        $zMax = 13;

        $greenStainedClay = VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN);
        for ($x = $xMin; $x < $xMax; ++$x) {
            for ($z = $zMin; $z < $zMax; ++$z) {
                /** @var StainedHardenedClay $block */
                $block = $world->getBlock(new Vector3($x, 49, $z));
                if ($block->getStateId() === $greenStainedClay->getStateId()) {
                    $selection->add($x, $block->getPosition()->getY(), $z, clone $block->setColor($dyeColors[array_rand($dyeColors)]));
                }
            }
        }

        AsyncBlockManager::executeSet($selection, $world);
    }

    public function putBigBlockStack(World $world, Block $block): void
    {
        $selection = new Selection();

        $xMin = 3;
        $xMax = 5;
        $yMin = 50;
        $yMax = 55;
        $zMin = -2;
        $zMax = 1;

        for ($x = $xMin; $x < $xMax; ++$x) {
            for ($y = $yMin; $y < $yMax; ++$y) {
                for ($z = $zMin; $z < $zMax; ++$z) {
                    $selection->add($x, $y, $z, $block);
                }
            }
        }

        AsyncBlockManager::executeSet($selection, $world);
    }

    public function setWitchGameBlocks(World $world): void
    {
        $selection = new Selection();

        $xMin = min(13, -11);
        $xMax = max(13, -11);
        $zMin = min(-13, 13);
        $zMax = max(-13, 13);
        $flowers = $this->getFlowers();

        for ($x = $xMin; $x < $xMax; ++$x) {
            for ($z = $zMin; $z < $zMax; ++$z) {
                $block = $world->getBlockAt($x, 49, $z);
                $blockAbove = $world->getBlockAt($x, 50, $z);

                if ($blockAbove->getTypeId() === BlockTypeIds::AIR && $block->getStateId() === VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN)->getStateId()) {
                    $selection->add($x, 49, $z, VanillaBlocks::GRASS());
                    $selection->add($x, 50, $z, $flowers[array_rand($flowers)]);
                }
            }
        }

        $selection->addSelection(BlockSets::WITCH_ADD_SELECTION());
        AsyncBlockManager::executeSet($selection, $world);
    }

    /**
     * @return Block[]
     */
    protected function getFlowers(): array
    {
        return [
            VanillaBlocks::ALLIUM(),
            VanillaBlocks::AZURE_BLUET(),
            VanillaBlocks::BLUE_ORCHID(),
            VanillaBlocks::DANDELION(),
            VanillaBlocks::LILY_OF_THE_VALLEY(),
            VanillaBlocks::ORANGE_TULIP(),
            VanillaBlocks::OXEYE_DAISY(),
            VanillaBlocks::PINK_TULIP(),
            VanillaBlocks::POPPY(),
            VanillaBlocks::RED_TULIP(),
            VanillaBlocks::WHITE_TULIP(),
        ];
    }

    public function resetWitchGameBlocks(World $world): void
    {
        $selection = new Selection();

        $xMin = min(13, -11);
        $xMax = max(13, -11);
        $zMin = min(-13, 13);
        $zMax = max(-13, 13);

        for ($x = $xMin; $x < $xMax; ++$x) {
            for ($z = $zMin; $z < $zMax; ++$z) {
                $block = $world->getBlockAt($x, 49, $z);

                if ($block->getTypeId() === BlockTypeIds::GRASS) {
                    $selection->add($x, 49, $z, VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN));
                    $selection->add($x, 50, $z, VanillaBlocks::AIR());
                }
            }
        }

        $selection->addSelection(BlockSets::WITCH_REMOVE_SELECTION());
        AsyncBlockManager::executeSet($selection, $world);
    }
}