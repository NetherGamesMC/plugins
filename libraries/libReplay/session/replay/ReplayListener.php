<?php

declare(strict_types=1);


namespace libReplay\session\replay;


use NetherGames\NGEssentials\events\NGPlayerTransferEvent;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class ReplayListener implements Listener
{
    /** @var ReplayManager */
    private ReplayManager $manager;

    public function __construct(ReplayManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param BlockGrowEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockForm(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param BlockUpdateEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param BlockBurnEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param CraftItemEvent $event
     *
     * @priority LOWEST
     */
    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param BlockSpreadEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param BlockGrowEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param NGPlayerTransferEvent $event
     *
     * @priority MONITOR
     */
    public function onNGPlayerTransfer(NGPlayerTransferEvent $event): void
    {
        $this->getManager()->getScoreboard()->removePlayer($event->getPlayer());
    }

    /**
     * @return ReplayManager
     */
    private function getManager(): ReplayManager
    {
        return $this->manager;
    }

    /**
     * @param DataPacketReceiveEvent $event
     *
     * @priority LOW
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $origin = $event->getOrigin();
        /** @var NGPlayer $player */
        $player = $origin->getPlayer();
        $packet = $event->getPacket();

        if ($packet->pid() === InventoryTransactionPacket::NETWORK_ID) {
            /** @var InventoryTransactionPacket $packet */
            if ($packet->trData instanceof UseItemOnEntityTransactionData) {
                if ($event->isCancelled() || ($replay = $this->getManager()->getReplay($player->getWorld())) === null) {
                    return;
                }

                if (($playerInformation = $replay->getPlayerInformation($packet->trData->getActorRuntimeId())) === null) {
                    return;
                }

                foreach (Permissions::STAFF_RANKS as $rank) {
                    if ($player->hasPermission($rank)) {
                        $replay->sendPlayerOptions($player, $playerInformation);
                        $event->cancel();
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param WorldLoadEvent $event
     *
     * @priority HIGHEST
     */
    public function onWorldLoad(WorldLoadEvent $event): void
    {
        $event->getWorld()->setAutoSave(false);
    }

    /**
     * @param LeavesDecayEvent $event
     *
     * @priority LOWEST
     */
    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        $event->cancel();
    }
}