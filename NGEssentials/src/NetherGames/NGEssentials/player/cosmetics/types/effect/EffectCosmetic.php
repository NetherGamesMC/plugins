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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\cosmetics\types\effect;

use libVanilla\sound\ThunderSound;
use NetherGames\NGEssentials\player\cosmetics\traits\EntityCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\traits\FireworkCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\traits\ParticleCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\world\Position;
use function mt_rand;

abstract class EffectCosmetic extends Cosmetic
{
    use ParticleCosmeticTrait;
    use FireworkCosmeticTrait;
    use EntityCosmeticTrait;

    public function run(Player $player, Position $pos): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            $entryData = $entry->getDataEntry();

            if ($this->isParticleCosmeticEntry($entryData)) {
                $this->getOptimizer()->addParticle($this->getParticle($entryData), $pos, $player->getWorld());
            } else if ($this->isFireworkCosmeticEntry($entryData)) {
                $entity = $this->getFirework($entryData, Position::fromObject($pos->add(mt_rand(-1, 1), 0, mt_rand(-1, 1)), $player->getWorld()));
                $entity?->spawnToAll();
            } else if ($this->isEntityCosmeticEntry($entryData)) {
                $playerManager = $this->getHandler()->getManager();

                $viewers = $pos->getWorld()->getViewersForPosition($pos);
                if (($entityId = $this->getEntityId($entryData)) === EntityIds::LIGHTNING_BOLT) {
                    [$fpsPlayers, $viewers] = $playerManager->splitFPSPlayers($viewers);
                    $player->getWorld()->addSound($pos, new ThunderSound(), $fpsPlayers);
                }

                NetworkBroadcastUtils::broadcastPackets($viewers, [
                    AddActorPacket::create(
                        $actorId = Entity::nextRuntimeId(),
                        $actorId,
                        $entityId,
                        $pos,
                        null,
                        0,
                        0,
                        0,
                        0,
                        [],
                        [],
                        new PropertySyncData([], []),
                        []
                    )
                ]);
            }
        }
    }
}