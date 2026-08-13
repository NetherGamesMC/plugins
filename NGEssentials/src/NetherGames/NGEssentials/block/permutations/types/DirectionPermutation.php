<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\permutations\types;

use InvalidArgumentException;
use NetherGames\NGEssentials\block\permutations\BlockProperty;
use NetherGames\NGEssentials\block\permutations\Permutation;
use pocketmine\block\Block;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

/**
 * @extends PermutationType<HorizontalFacingTrait&Block>
 */
class DirectionPermutation extends PermutationType
{
    /**
     * @param HorizontalFacingTrait&Block $block
     */
    public function getBlockProperty(Block $block): BlockProperty
    {
        return new BlockProperty(BlockStateNames::FACING_DIRECTION, [
            new IntTag(Facing::NORTH),
            new IntTag(Facing::EAST),
            new IntTag(Facing::SOUTH),
            new IntTag(Facing::WEST),
        ]);
    }

    /**
     * @param HorizontalFacingTrait&Block $block
     */
    public function getCurrentBlockProperty(Block $block): int
    {
        return $block->getFacing();
    }

    /**
     * @param HorizontalFacingTrait&Block $block
     * @return Permutation[]
     */
    public function getPermutations(Block $block): array
    {
        /** @var IntTag[] $values */
        $values = $this->getBlockProperty($block)->getValues();

        return array_map(
            fn(IntTag $value) => (new Permutation("q.block_property('" . BlockStateNames::FACING_DIRECTION . "') == " . $value->getValue()))
                ->withComponent("minecraft:transformation", CompoundTag::create()
                    ->setInt("RX", 0)
                    ->setInt("RY", match ($value->getValue()) {
                        Facing::NORTH => 2,
                        Facing::EAST => 1,
                        Facing::SOUTH => 0,
                        Facing::WEST => 3,
                        default => throw new InvalidArgumentException("Invalid facing direction value: " . $value->getValue()),
                    })
                    ->setInt("RZ", 0)
                    ->setFloat("SX", 1)
                    ->setFloat("SY", 1)
                    ->setFloat("SZ", 1)
                    ->setFloat("TX", 0)
                    ->setFloat("TY", 0)
                    ->setFloat("TZ", 0)),
            $values
        );
    }

    /**
     * @param HorizontalFacingTrait&Block $block
     */
    public function describeBlockOnlyState(Block $block, RuntimeDataDescriber $w): void
    {
        $facing = $block->getFacing();

        $w->horizontalFacing($facing);

        $block->setFacing($facing);
    }

    /**
     * @param HorizontalFacingTrait&Block $block
     */
    public function serializeState(Block $block, BlockStateWriter $blockStateOut): void
    {
        $blockStateOut->writeHorizontalFacing($block->getFacing());
    }

    /**
     * @param HorizontalFacingTrait&Block $block
     */
    public function deserializeState(Block $block, BlockStateReader $blockStateIn): void
    {
        $block->setFacing($blockStateIn->readHorizontalFacing());
    }
}