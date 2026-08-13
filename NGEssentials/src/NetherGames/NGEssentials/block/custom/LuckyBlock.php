<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\custom;

use NetherGames\NGEssentials\block\BlockComponents;
use NetherGames\NGEssentials\block\BlockComponentsTrait;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;

class LuckyBlock extends Opaque implements BlockComponents
{
    use BlockComponentsTrait;

    public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo)
    {
        parent::__construct($idInfo, $name, $typeInfo);
        $this->initComponent("ng:skywars_lucky_block");
    }
}