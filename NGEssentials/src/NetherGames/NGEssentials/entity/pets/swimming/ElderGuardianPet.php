<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
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

namespace NetherGames\NGEssentials\entity\pets\swimming;

use libVanilla\entity\Monster;
use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use function mt_rand;

class ElderGuardianPet extends Monster implements IPetEntity
{
    use SwimmingTrait {
        SwimmingTrait::attack as private baseAttack;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ELDER_GUARDIAN;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $this->baseAttack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent) {
            $attacker = $source->getDamager();
            if ($attacker instanceof Player && mt_rand(0, 1)) {
                $attacker->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::GUARDIAN_CURSE, 0, null));
            }
        }
    }

    protected function initPetData(CompoundTag $nbt): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::ELDER, true);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.99, 1.99);
    }
}