<?php

declare(strict_types=1);

namespace skywars\drops\list;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use skywars\drops\BaseDrop;
use skywars\entities\LuckyBlock;
use skywars\SWArena;
use skywars\utils\SoundNames;

class LightningStrike extends BaseDrop
{
    public function dropChance(): float|int
    {
        return 50;
    }

    public function getPriority(): int
    {
        return self::PRIORITY_MEDIUM;
    }

    public function drop(Player $player, LuckyBlock $block, SWArena $arena): void
    {
        $location = $block->getLocation();

        $entityId = Entity::nextRuntimeId();
        $packet = AddActorPacket::create(
            $entityId,
            $entityId,
            EntityIds::LIGHTNING_BOLT,
            $block->getPosition(),
            null,
            0,
            0,
            0,
            0,
            [],
            [],
            new PropertySyncData([], []),
            []
        );
        $player->getWorld()->broadcastPacketToViewers($location->asVector3(), $packet);

        $this->playSound($location, SoundNames::SOUND_AMBIENT_WEATHER_THUNDER);

        $vector = $location->floor();
        $selection = new Selection();

        for ($x = -1; $x <= 1; $x++) {
            for ($z = -1; $z <= 1; $z++) {
                $selection->add($vector->x + $x, $vector->y, $vector->z + $z, VanillaBlocks::FIRE());
            }
        }

        AsyncBlockManager::executeSet($selection, $location->getWorld());
    }
}