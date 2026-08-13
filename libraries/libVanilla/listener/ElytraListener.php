<?php

declare(strict_types=1);

namespace libVanilla\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class ElytraListener implements Listener
{
    /**
     * @param PlayerMoveEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player->isGliding() && $player->isOnGround() && $player->getNetworkSession()->getProtocolId() >= ProtocolInfo::PROTOCOL_1_20_10) {
            $player->toggleGlide(false);
        }
    }
}