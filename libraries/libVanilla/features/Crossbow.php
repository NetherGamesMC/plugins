<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\features;

use libVanilla\entity\object\CrossbowArrow;
use libVanilla\item\Crossbow as CrossbowItem;
use libVanilla\network\PacketHandler;
use libVanilla\network\PacketProcessor;
use libVanilla\VanillaPlugin;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\Arrow;
use pocketmine\item\ItemUseResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\sound\ItemBreakSound;
use pocketmine\world\World;

class Crossbow extends Feature implements PacketHandler
{
    protected function setup(PluginBase $plugin): void
    {
        VanillaPlugin::ENCHANTS()->register($plugin);

        EntityFactory::getInstance()->register(CrossbowArrow::class, function (World $world, CompoundTag $nbt): Arrow {
            $pierceCount = $nbt->getInt(CrossbowArrow::TAG_PIERCE_COUNT, -1);
            if ($pierceCount !== -1) {
                $entity = new CrossbowArrow(EntityDataHelper::parseLocation($nbt, $world), null, $pierceCount, $nbt);
                $entity->setCritical(false);
                return $entity;
            }
            return new Arrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
        }, ['Arrow', 'minecraft:arrow']);

        PacketProcessor::getInstance()->registerHandler($this, $plugin);
    }

    private function triggerClick(Player $player): bool
    {
        if (!($item = $player->getInventory()->getItemInHand()) instanceof CrossbowItem) {
            return false;
        }
        $oldItem = clone $item;
        $returnedItems = [];
        $result = $item->onClickAir($player, $player->getDirectionVector(), $returnedItems);
        $inventory = $player->getInventory();

        if ($result->equals(ItemUseResult::FAIL())) {
            $player->getNetworkSession()->getInvManager()->syncSelectedHotbarSlot();
            return false;
        }

        if ($item->isBroken()) {
            $player->broadcastSound(new ItemBreakSound());
        }

        $player->resetItemCooldown($oldItem);
        $inventory->setItemInHand($item);
        $player->setUsingItem($oldItem->equals($item));
        return true;
    }

    public function handlePlayerAction(NetworkSession $origin, InventoryTransactionPacket $packet): bool
    {
        if (!$packet->trData instanceof UseItemTransactionData || $packet->trData->getActionType() !== UseItemTransactionData::ACTION_CLICK_AIR) {
            return false;
        }
        if (($player = $origin->getPlayer()) === null) {
            return false;
        }

        return $this->triggerClick($player);
    }
}