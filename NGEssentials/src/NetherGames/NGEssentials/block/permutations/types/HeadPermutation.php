<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\permutations\types;

use NetherGames\NGEssentials\block\permutations\BlockProperty;
use NetherGames\NGEssentials\block\permutations\Permutation;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;

class HeadPermutation extends PermutationType
{
    public function __construct(private readonly string $geometryBasePath)
    {
    }

    /**
     * @param Bed $block
     */
    public function getBlockProperty(Block $block): BlockProperty
    {
        return new BlockProperty(BlockStateNames::HEAD_PIECE_BIT, [
            new ByteTag(0),
            new ByteTag(1),
        ]);
    }

    /**
     * @param Bed $block
     */
    public function getCurrentBlockProperty(Block $block): int
    {
        return (int)$block->isHeadPart();
    }

    /**
     * @param Bed $block
     * @return Permutation[]
     */
    public function getPermutations(Block $block): array
    {
        /** @var ByteTag[] $values */
        $values = $this->getBlockProperty($block)->getValues();

        return array_map(
            fn(ByteTag $value) => (new Permutation("q.block_property('" . BlockStateNames::HEAD_PIECE_BIT . "') == " . $value->getValue()))
                ->withComponent("minecraft:geometry", CompoundTag::create()
                    ->setString("identifier", $this->geometryBasePath . '.' . ($value->getValue() === 0 ? 'foot' : 'head'))),
            $values
        );
    }

    /**
     * @param Bed $block
     */
    public function describeBlockOnlyState(Block $block, RuntimeDataDescriber $w): void
    {
        $head = $block->isHeadPart();

        $w->bool($head);

        $block->setHead($head);
    }

    /**
     * @param Bed $block
     */
    public function serializeState(Block $block, BlockStateWriter $blockStateOut): void
    {
        $blockStateOut->writeBool(BlockStateNames::HEAD_PIECE_BIT, $block->isHeadPart());
    }

    /**
     * @param Bed $block
     */
    public function deserializeState(Block $block, BlockStateReader $blockStateIn): void
    {
        $block->setHead($blockStateIn->readBool(BlockStateNames::HEAD_PIECE_BIT));
    }
}