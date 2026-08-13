<?php

namespace NetherGames\NGEssentials\block\custom;

use NetherGames\NGEssentials\block\BlockComponents;
use NetherGames\NGEssentials\block\BlockComponentsTrait;
use NetherGames\NGEssentials\block\BlockPermutationsTrait;
use NetherGames\NGEssentials\block\permutations\Permutable;
use NetherGames\NGEssentials\block\permutations\types\DirectionPermutation;
use NetherGames\NGEssentials\block\permutations\types\HeadPermutation;
use pocketmine\block\Bed;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;

class CustomBed extends Bed implements BlockComponents, Permutable
{
    use BlockComponentsTrait, BlockPermutationsTrait;

    public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo, string $texture, string $geometryPath)
    {
        $this->addPermutationType(new HeadPermutation($geometryPath));
        $this->addPermutationType(new DirectionPermutation());

        parent::__construct($idInfo, $name, $typeInfo);

        $this->initComponent($texture);
    }
}