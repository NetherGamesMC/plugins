<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\utils\SignText;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Villager;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

class KeithVillager extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 50;
    }

    public function getPriority(): int
    {
        return self::PRIORITY_MEDIUM;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $location = clone $player->getLocation();
        $direction = $player->getDirectionVector()->multiply(3)->floor();

        $location->x += $direction->x;
        $location->z += $direction->z;

        $vector = $location->floor();
        $selection = new Selection();

        for ($x = -1; $x <= 1; $x++) {
            for ($z = -1; $z <= 1; $z++) {
                for ($y = -1; $y <= 2; $y++) {
                    if ($x > -1 && $x < 1 && $z > -1 && $z < 1 && $y > -1 && $y < 2) {
                        continue;
                    }
                    $selection->add($vector->x + $x, $vector->y + $y, $vector->z + $z, VanillaBlocks::GLASS());
                }
            }
        }

        $villager = new Villager($location);
        $villager->setNameTag("Keith");
        $villager->setNameTagAlwaysVisible();
        $villager->spawnToAll();

        $villager->lookAt($player->getLocation()->asVector3());

        $sign = VanillaBlocks::OAK_WALL_SIGN();
        $sign->setFacing(Facing::opposite($player->getHorizontalFacing()));
        $sign->setText(new SignText(['', 'Dangerous', '', '']));
        $player->getWorld()->setBlock($villager->getTargetBlock(2)->getPosition()->asVector3()->floor(), $sign);

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}