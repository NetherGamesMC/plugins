<?php

namespace NetherGames\NGEssentials\block;

use NetherGames\NGEssentials\block\permutations\BlockProperty;
use NetherGames\NGEssentials\block\permutations\Permutation;
use NetherGames\NGEssentials\block\permutations\types\PermutationType;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\RuntimeDataDescriber;
use function array_map;
use function array_merge;

trait BlockPermutationsTrait
{
    /** @var PermutationType[] */
    private array $permutationTypes = [];

    public function addPermutationType(PermutationType $permutation): void
    {
        $this->permutationTypes[] = $permutation;
    }

    /**
     * @return PermutationType[]
     */
    public function getPermutationTypes(): array
    {
        return $this->permutationTypes;
    }

    /**
     * @return BlockProperty[]
     */
    public function getBlockProperties(): array
    {
        return array_map(
            fn(PermutationType $permutation): BlockProperty => $permutation->getBlockProperty($this),
            $this->getPermutationTypes()
        );
    }

    public function getCurrentBlockProperties(): array
    {
        return array_map(
            fn(PermutationType $permutation): int => $permutation->getCurrentBlockProperty($this),
            $this->getPermutationTypes()
        );
    }

    /**
     * @return Permutation[]
     */
    public function getPermutations(): array
    {
        return array_merge(
            ...array_map(
                fn(PermutationType $permutation): array => $permutation->getPermutations($this),
                $this->getPermutationTypes()
            )
        );
    }

    public function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        foreach ($this->permutationTypes as $permutationType) {
            $permutationType->describeBlockOnlyState($this, $w);
        }
    }

    public function serializeState(BlockStateWriter $blockStateOut): void
    {
        foreach ($this->permutationTypes as $permutationType) {
            $permutationType->serializeState($this, $blockStateOut);
        }
    }

    public function deserializeState(BlockStateReader $blockStateIn): void
    {
        foreach ($this->permutationTypes as $permutationType) {
            $permutationType->deserializeState($this, $blockStateIn);
        }
    }
}