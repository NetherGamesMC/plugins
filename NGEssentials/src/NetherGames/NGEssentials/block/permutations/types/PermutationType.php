<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\permutations\types;

use NetherGames\NGEssentials\block\permutations\BlockProperty;
use NetherGames\NGEssentials\block\permutations\Permutation;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\RuntimeDataDescriber;

/**
 * @template T of Block
 */
abstract class PermutationType
{
    /**
     * @param T $block
     */
    abstract public function getBlockProperty(Block $block): BlockProperty;

    /**
     * @param T $block
     */
    abstract public function getCurrentBlockProperty(Block $block): int;

    /**
     * @param T $block
     * @return Permutation[]
     */
    abstract public function getPermutations(Block $block): array;

    /**
     * @param T $block
     */
    abstract public function describeBlockOnlyState(Block $block, RuntimeDataDescriber $w): void;

    /**
     * @param T $block
     */
    abstract public function serializeState(Block $block, BlockStateWriter $blockStateOut): void;

    /**
     * @param T $block
     */
    abstract public function deserializeState(Block $block, BlockStateReader $blockStateIn): void;
}