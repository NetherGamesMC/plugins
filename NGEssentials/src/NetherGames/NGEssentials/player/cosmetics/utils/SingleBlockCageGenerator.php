<?php

namespace NetherGames\NGEssentials\player\cosmetics\utils;

use Closure;
use libasyncio\blocks\Selection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class SingleBlockCageGenerator
{
    /**
     * @param Closure(Block $block): Block $blockProcessor
     */
    public static function generateCage(Block $block, int $size, ?Closure $blockProcessor = null): Selection
    {
        $selection = new Selection();
        $height = 3;

        // FLOOR
        self::generateWall($selection, new Vector3(-$size, -1, -$size), new Vector3($size, -1, $size), $block, $blockProcessor);

        // WALLS
        self::generateWall($selection, new Vector3(-$size, 0, $size), new Vector3($size, $height - 1, $size), $block, $blockProcessor);
        self::generateWall($selection, new Vector3(-$size, 0, -$size), new Vector3(-$size, $height - 1, $size), $block, $blockProcessor);
        self::generateWall($selection, new Vector3(-$size, 0, -$size), new Vector3($size, $height - 1, -$size), $block, $blockProcessor);
        self::generateWall($selection, new Vector3($size, 0, -$size), new Vector3($size, $height - 1, $size), $block, $blockProcessor);

        // CEILING
        self::generateWall($selection, new Vector3(-$size, $height, -$size), new Vector3($size, $height, $size), $block, $blockProcessor);

        return $selection;
    }

    /**
     * @param Closure(Block $block): Block $blockProcessor
     */
    private static function generateWall(Selection $selection, Vector3 $startVector, Vector3 $endVector, Block $block, ?Closure $blockProcessor = null): void
    {
        for ($x = $startVector->getFloorX(); $x <= $endVector->getFloorX(); $x++) {
            for ($y = $startVector->getFloorY(); $y <= $endVector->getFloorY(); $y++) {
                for ($z = $startVector->getFloorZ(); $z <= $endVector->getFloorZ(); $z++) {
                    $selection->add($x, $y, $z, $blockProcessor ? $blockProcessor($block) : $block);
                }
            }
        }
    }
}