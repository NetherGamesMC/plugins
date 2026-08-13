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

namespace libVanilla\item;

use libVanilla\session\WorldSessionManager;
use libVanilla\sound\TridentRiptideSound;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\ItemUseResult;
use pocketmine\item\Trident as PMTrident;
use pocketmine\player\Player;

class Trident extends PMTrident
{

    private function checkRiptideWaterRequirement(Player $player): bool
    {
        if ($player->isUnderwater()) {
            return true;
        }

        $world = $player->getWorld();
        if (WorldSessionManager::getInstance()->get($world)->getCurrentWeather()->isRainy()) {
            $playerLocation = $player->getLocation();
            // check if player "can see sky". if the player is covered, there's no rain... no rain = no riptide
            return $world->getHighestBlockAt($playerLocation->getX(), $playerLocation->getZ()) < $player->getEyePos()->getY();
        }

        return false;
    }

    public function onReleaseUsing(Player $player, array &$returnedItems): ItemUseResult
    {
        $riptide = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::RIPTIDE);
        if (($riptideLevel = $this->getEnchantmentLevel($riptide)) > 0) {
            if ($player->getItemUseDuration() < 10 || !$this->checkRiptideWaterRequirement($player)) {
                return ItemUseResult::FAIL();
            }
            if ($player->isSurvival()) {
                $this->applyDamage(1);
            }
            $player->broadcastSound(new TridentRiptideSound($riptideLevel));
            return ItemUseResult::SUCCESS();
        }

        return parent::onReleaseUsing($player, $returnedItems);
    }

    public function canStartUsingItem(Player $player): bool
    {
        $riptide = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::RIPTIDE);
        return !($this->hasEnchantment($riptide) && !$this->checkRiptideWaterRequirement($player));
    }
}