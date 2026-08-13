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

namespace conquests\utils;

use conquests\CQArena;
use libasyncio\blocks\Selection;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlockPlaceSound;
use pocketmine\world\World;
use function ksort;

final class PopupTower
{
    public static function buildCompact(Player $player, Position $position, CQArena $arena): void
    {
        $world = $position->getWorld();
        $layers = self::getLayers($position, $arena->getTeam($player)->getDyeColor(), Facing::opposite($player->getHorizontalFacing()));
        $blockFactory = RuntimeBlockStateRegistry::getInstance();

        $arena->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($world, $arena, $blockFactory, &$layers): void {
            /** @var Selection|null $layer */
            $layer = array_shift($layers);

            if ($layer === null || !$world->isLoaded()) {
                throw new CancelTaskException();
            }

            foreach ($layer->getBlocks() as $hash => $blockStateId) {
                World::getBlockXYZ($hash, $x, $y, $z);

                if ($world->getBlockAt($x, $y, $z)->getTypeId() === BlockTypeIds::AIR && $arena->getListener()->canPlaceBlock($vec3 = new Vector3($x, $y, $z)) === null) {
                    $block = $blockFactory->fromStateId($blockStateId);

                    $world->addSound($vec3, new BlockPlaceSound($block));
                    $world->addParticle($vec3, new BlockBreakParticle($block));
                    $world->setBlockAt($x, $y, $z, $block, false);

                    $arena->getBlockCollector()->addBlock($vec3);
                }
            }
        }), 3);
    }

    /**
     * @phpstan-param  array<int, Selection> $layers
     */
    private static function getLayer(array &$layers, int $y): Selection
    {
        return $layers[$y] ??= new Selection();
    }

    /**
     * @phpstan-param  array<int, Selection> $layers
     */
    private static function addToLayer(array &$layers, int $x, int $y, int $z, int $blockStateId): void
    {
        self::getLayer($layers, $y)->addRaw(World::blockHash($x, $y, $z), $blockStateId);
    }

    /**
     * @phpstan-return array<int, Selection>
     */
    private static function getLayers(Position $position, DyeColor $dyeColor, int $facing): array
    {
        $air = VanillaBlocks::AIR()->getStateId();
        $ladder = VanillaBlocks::LADDER()->setFacing($facing)->getStateId();
        $wool = VanillaBlocks::WOOL()->setColor($dyeColor)->getStateId();
        $posX = $position->getFloorX();
        $posY = $position->getFloorY();
        $posZ = $position->getFloorZ();

        $layers = [];

        // Top
        for ($x = -3; $x <= 3; ++$x) {
            for ($y = 4; $y <= 6; ++$y) {
                for ($z = -3; $z <= 3; ++$z) {
                    if ($x !== -3 && $x !== 3 && $z !== -3 && $z !== 3) {
                        continue;
                    }

                    // Crown shape's top layer
                    if ($y === 6 && ($x === -3 || $x === 3 || $z === -3 || $z === 3) && ($x % 2 === 0 || $z % 2 === 0)) {
                        continue;
                    }

                    // Crown shape's bottom layer
                    if ($y === 4 && ($x === -3 || $x === 3 || $z === -3 || $z === 3) && ($x % 3 !== 0 || $z % 3 !== 0)) {
                        continue;
                    }

                    self::addToLayer($layers, $posX + $x, $posY + $y, $posZ + $z, $wool);
                }
            }
        }

        // Roof
        for ($x = -2; $x <= 2; ++$x) {
            for ($y = 4; $y <= 4; ++$y) {
                for ($z = -2; $z <= 2; ++$z) {
                    self::addToLayer($layers, $posX + $x, $posY + $y, $posZ + $z, $wool);
                }
            }
        }

        // Corners
        for ($x = -2; $x <= 2; ++$x) {
            for ($y = 0; $y < 4; ++$y) {
                for ($z = -2; $z <= 2; ++$z) {
                    if ($x !== -2 && $x !== 2 && $z !== -2 && $z !== 2) {
                        continue;
                    }

                    self::addToLayer($layers, $posX + $x, $posY + $y, $posZ + $z, $wool);
                }
            }
        }

        // Ladders
        $ladderPos = $position->getSide($facing, -1);
        for ($y = 0; $y <= 4; ++$y) {
            self::addToLayer($layers, $ladderPos->getFloorX(), $ladderPos->getFloorY() + $y, $ladderPos->getFloorZ(), $ladder);
        }

        // Door
        $doorPos = $position->getSide($facing, 2);
        for ($y = 0; $y <= 2; ++$y) {
            self::addToLayer($layers, $doorPos->getFloorX(), $doorPos->getFloorY() + $y, $doorPos->getFloorZ(), $air);
        }

        ksort($layers, SORT_NUMERIC);

        return $layers;
    }
}