<?php
declare(strict_types=1);

namespace uhc\voting;

use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;

class Items extends \libminigames\utils\Items
{
    public static function getScenarios(): Item
    {
        return VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER)->asItem()->setCustomName('§r§6§lScenarios');
    }
}