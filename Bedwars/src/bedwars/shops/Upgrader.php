<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\shops;

use bedwars\BWTeam;
use bedwars\shops\form\UpgraderForm;
use bedwars\shops\menu\UpgraderMenu;
use bedwars\utils\TrapManager;
use bedwars\utils\Utils;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Sword;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FireExtinguishSound;
use function count;

final class Upgrader
{

    private UpgraderMenu $menu;
    private UpgraderForm $form;

    public function __construct()
    {
        $this->menu = new UpgraderMenu($this);
        $this->form = new UpgraderForm($this);
    }

    public function send(Player $player, BWTeam $team, bool $chestUI): void
    {
        if ($chestUI) {
            $this->menu->send($player, $team);
        } else {
            $this->form->send($player, $team);
        }
    }

    public function queue(Player $player, BWTeam $team, Trap $trap, int $slot): bool
    {
        /** @var NGPlayer $player */
        $currentTraps = $team->getTrapManager()->getTraps();
        $errorMessage = match (true) {
            count($currentTraps) === TrapManager::MAX_QUEUED_TRAPS => "The trap queue is currently full!",
            $slot <= count($team->getTrapManager()->getTraps()) => "Trap #$slot has already been bought!",
            default => null
        };
        if ($errorMessage !== null) {
            $player->sendConditionalMessage(TextFormat::RED . $errorMessage);
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return false;
        }

        // Check if an error occurred while adding the trap to the queue.
        if (!$team->getTrapManager()->add($trap)) {
            $player->sendMessage(TextFormat::RED . "An error occurred while adding the trap to the queue!");
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return false;
        }
        $player->playSound("random.orb");
        $team->broadcastMessage(TextFormat::GREEN . NGEssentials::getInstance()->getPlayerManager()->getPlayerName($player) . " purchased " . TextFormat::GOLD . $trap->name);
        return true;
    }

    public function upgrade(Player $player, BWTeam $team, Upgrade $upgrade, int $level): bool
    {
        /** @var NGPlayer $player */

        $error = match (true) {
            $upgrade->hasTiers() && $upgrade->getTier($level) === null => "That upgrade is already maxed out!",
            $level <= $team->getUpgradeLevel($upgrade) => "That upgrade has already been bought!",
            default => null
        };

        if ($error !== null) {
            $player->sendConditionalMessage(TextFormat::RED . $error);
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return false;
        }

        $team->setUpgradeLevel($upgrade, $level);

        switch ($upgrade) {
            case Upgrade::MINER():
                foreach ($team->getAlivePlayers() as $alivePlayer) {
                    $alivePlayer->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), Limits::INT32_MAX, $level - 1, false));
                }
                break;
            case Upgrade::SWORDS():
                foreach ($team->getAlivePlayers() as $alivePlayer) {
                    /** @var array<Inventory> $inventories */
                    $inventories = [$alivePlayer->getInventory(), $alivePlayer->getEnderInventory()];
                    foreach ($inventories as $inventory) {
                        foreach ($inventory->getContents() as $index => $item) {
                            if ($item instanceof Sword || $item instanceof Axe) {
                                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $level));
                                $inventory->setItem($index, $item);
                            }
                        }
                    }
                }
                break;
            case Upgrade::ARMOR():
                foreach ($team->getAlivePlayers() as $alivePlayer) {
                    $armor = $alivePlayer->getArmorInventory();
                    foreach ($armor->getContents() as $index => $armorPiece) {
                        if (!$armorPiece->isNull()) {
                            $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
                            $armor->setItem($index, $armorPiece);
                        }
                    }
                }
                break;
            case Upgrade::SOFT_BOOTS():
                foreach ($team->getAlivePlayers() as $alivePlayer) {
                    $armor = $alivePlayer->getArmorInventory();
                    $boots = $armor->getBoots();
                    if (!$boots->isNull()) {
                        $boots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), $level));
                        $armor->setItem(ArmorInventory::SLOT_FEET, $boots);
                    }
                }
                break;
            case Upgrade::BLAST_PROTECTION():
                foreach ($team->getAlivePlayers() as $alivePlayer) {
                    $armor = $alivePlayer->getArmorInventory();
                    foreach ($armor->getContents() as $index => $armorPiece) {
                        if (!$armorPiece->isNull()) {
                            $armorPiece->addEnchantment(new EnchantmentInstance(VanillaEnchantments::BLAST_PROTECTION(), 2));
                            $armor->setItem($index, $armorPiece);
                        }
                    }
                }
                break;
        }

        $tier = $upgrade->getTier($level) ?? throw new AssumptionFailedError("Tier should exist");
        $name = $tier->customName ?: ("$upgrade->name " . ($upgrade->hasTiers() ? Utils::getRomanNumber($level) : ""));
        $team->broadcastMessage(TextFormat::GREEN . $team->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player) . " purchased " . TextFormat::GOLD . $name);

        foreach ($team->getAlivePlayers() as $alivePlayer) {
            /** @var NGPlayer $alivePlayer */
            $alivePlayer->playSound("beacon.power");
        }
        return true;
    }
}