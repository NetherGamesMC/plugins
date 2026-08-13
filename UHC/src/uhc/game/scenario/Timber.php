<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use pocketmine\block\Block;
use pocketmine\block\Wood;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use uhc\game\scenario\base\Scenario;

class Timber extends Scenario
{

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if ($event->getPlayer()->getGamemode() !== GameMode::SURVIVAL) {
            return;
        }

        if ($block instanceof Wood) {
            $this->breakWood($event->getPlayer(), $block);
        }
    }

    private function breakWood(Player $player, Block $block): void
    {
        foreach ($block->getAllSides() as $blockSide) {
            if ($blockSide->getTypeId() === $block->getTypeId()) {
                $player->getWorld()->useBreakOn($blockSide->getPosition());
                $this->breakWood($player, $blockSide);
            }
        }
    }
}