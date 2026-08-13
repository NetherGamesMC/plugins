<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\games;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use mommasays\utils\entity\ArmorStand;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Armor;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use function array_map;
use function array_merge;
use function array_rand;
use function shuffle;

class GameArmorStand extends Game
{
    /** @var ArmorStand */
    private ArmorStand $armorStand;

    public function getMessage(): string
    {
        return 'Equip the armour of the armour stand';
    }

    public function setupArena(): void
    {
        $selection = new Selection();
        $selection->addBlock(new Vector3(0, 50, -1), $block = VanillaBlocks::DIAMOND());
        $selection->addBlock(new Vector3(1, 50, -1), $block);
        $selection->addBlock(new Vector3(1, 50, 0), $block);
        $selection->addBlock(new Vector3(0, 50, 0), $block);

        AsyncBlockManager::executeSet($selection, $this->getArena()->getWorld(), function (): void {
            $equips = [
                VanillaItems::LEATHER_CAP(),
                VanillaItems::LEATHER_TUNIC(),
                VanillaItems::LEATHER_PANTS(),
                VanillaItems::LEATHER_BOOTS()
            ];

            $contents = array_map(function (Armor $armor) {
                return $this->applyRandomColor($armor);
            }, $equips);

            $armorStand = new ArmorStand(Location::fromObject(new Vector3(1, 51, 0), $this->getArena()->getWorld()));
            $armorStand->getArmorInventory()->setContents($contents);
            $armorStand->spawnToAll();
            $this->armorStand = $armorStand;

            $inventory = [];
            $toFill = 36 - 4;
            for ($i = 0; $i < $toFill; $i++) {
                $inventory[] = $this->applyRandomColor(clone $equips[array_rand($equips)]);
            }

            $inventory = array_merge($inventory, $contents);
            shuffle($inventory);

            foreach ($this->getArena()->getAlivePlayers() as $player) {
                $player->getInventory()->setContents($inventory);
            }
        });
    }

    private function applyRandomColor(Armor $armor): Armor
    {
        $all = DyeColor::cases();
        $dyeColor = $all[array_rand($all)];

        return $armor->setCustomColor($dyeColor->getRgbValue());
    }

    public function resetArena(): void
    {
        $this->armorStand->flagForDespawn();

        $selection = new Selection();
        $selection->addBlock(new Vector3(0, 50, -1), $air = VanillaBlocks::AIR());
        $selection->addBlock(new Vector3(1, 50, -1), $air);
        $selection->addBlock(new Vector3(1, 50, 0), $air);
        $selection->addBlock(new Vector3(0, 50, 0), $air);

        AsyncBlockManager::executeSet($selection, $this->getArena()->getWorld());

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->getArmorInventory()->clearAll();
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();
                $inventory->setItem($action->getSlot(), $action->getTargetItem());

                if (($inventory instanceof ArmorInventory) && $this->compareInventory($inventory) && !$this->isWinner($player->getName())) {
                    $this->addWinner($player);
                    $event->cancel();
                }
            }
        }
    }

    public function compareInventory(ArmorInventory $armorInventory): bool
    {
        $armorInventoryStand = $this->armorStand->getArmorInventory();

        foreach ($armorInventory->getContents(true) as $slot => $item) {
            if (!$item->equals($armorInventoryStand->getItem($slot))) {
                return false;
            }
        }

        return true;
    }
}