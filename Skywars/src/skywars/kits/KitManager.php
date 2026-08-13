<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author xBeastMode
 *
 */
declare(strict_types=1);

namespace skywars\kits;

use pocketmine\item\Armor;
use pocketmine\player\Player;
use skywars\kits\insane\Aquaman as InsaneAquaman;
use skywars\kits\insane\Archer as InsaneArcher;
use skywars\kits\insane\Ecologist as InsaneEcologist;
use skywars\kits\insane\Enderman as InsaneEnderman;
use skywars\kits\insane\Generic as InsaneGeneric;
use skywars\kits\insane\Healer as InsaneHealer;
use skywars\kits\insane\Pyro as InsanePyro;
use skywars\kits\insane\Sumo as InsaneSumo;
use skywars\kits\insane\Swordsman as InsaneSwordsman;
use skywars\kits\insane\Tracker as InsaneTracker;
use skywars\kits\normal\Archer as NormalArcher;
use skywars\kits\normal\Ecologist as NormalEcologist;
use skywars\kits\normal\Enderman as NormalEnderman;
use skywars\kits\normal\Generic as NormalGeneric;
use skywars\kits\normal\Healer as NormalHealer;
use skywars\kits\normal\Pyro as NormalPyro;
use skywars\kits\normal\Sumo as NormalSumo;
use skywars\kits\normal\Swordsman as NormalSwordsman;
use skywars\kits\normal\Tracker as NormalTracker;
use skywars\SWArena;

class KitManager
{

    /** @var Kit[][] */
    private array $kits = [];

    public function __construct()
    {
        $this->addKits();
    }

    private function addKits(): void
    {
        foreach (SWArena::getTypes() as $gameType) {
            $this->kits[$gameType] = [];
        }

        $this->registerInsaneKits();
        $this->registerNormalKits();
    }

    private function registerInsaneKits(): void
    {
        $this->addKit(SWArena::TYPE_INSANE, new InsaneAquaman());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneArcher());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneEcologist());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneEnderman());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneGeneric());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneHealer());
        $this->addKit(SWArena::TYPE_INSANE, new InsanePyro());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneSumo());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneSwordsman());
        $this->addKit(SWArena::TYPE_INSANE, new InsaneTracker());
    }

    private function addKit(int $type, Kit $kit): void
    {
        $this->kits[$type][$kit->getId()] = $kit;
    }

    private function registerNormalKits(): void
    {
        $this->addKit(SWArena::TYPE_NORMAL, new NormalArcher());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalEcologist());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalEnderman());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalGeneric());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalHealer());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalPyro());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalSumo());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalSwordsman());
        $this->addKit(SWArena::TYPE_NORMAL, new NormalTracker());
    }

    public function giveKit(Player $player, Kit $kit): void
    {
        foreach ($kit->getItems() as $item) {
            if (($item instanceof Armor) && ($armorInventory = $player->getArmorInventory())->isSlotEmpty($slot = $item->getArmorSlot())) {
                $armorInventory->setItem($slot, $item);
                continue;
            }

            $player->getInventory()->addItem($item);
        }
    }

    public function getKit(int $type, int $kitId): ?Kit
    {
        return $this->kits[$type][$kitId] ?? null;
    }

    public function getType(int $type): ?array
    {
        return $this->kits[$type] ?? null;
    }
}