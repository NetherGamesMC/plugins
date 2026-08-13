<?php
/**
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

namespace conquests\generators;


use conquests\Conquests;
use conquests\CQArena;
use conquests\utils\entity\ItemEntity;
use conquests\utils\StatsData;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use pocketmine\world\sound\PopSound;
use function array_filter;
use function count;

abstract class Generator
{
    /** @var int */
    protected int $time = 0;
    /** @var Location */
    private Location $location;
    /** @var AxisAlignedBB */
    private AxisAlignedBB $bb;

    public function __construct(Location $location, AxisAlignedBB $bb)
    {
        $this->location = $location;
        $this->bb = $bb;
    }

    abstract public function tick(): void;

    public function dropItem(Item $item, bool $multiplePlayers): void
    {
        $world = $this->location->getWorld();
        $entities = $world->getNearbyEntities($this->bb);

        if ($multiplePlayers) {
            /** @var Player[] $players */
            $players = array_filter($entities, static function (Entity $entity): bool {
                return $entity instanceof Player && $entity->canBeCollidedWith();
            });

            if (count($players) > 0) {
                foreach ($players as $player) {
                    $playerInventory = $player->getInventory();

                    if ($player->hasFiniteResources() && !$playerInventory->canAddItem($item)) {
                        continue;
                    }

                    $playerInventory->addItem($item);
                    self::onGeneratorItemPickup($player, $item);
                }

                return;
            }
        }

        /** @var ItemEntity[] $itemEntities */
        $itemEntities = array_filter($entities, static function (Entity $entity) use ($item): bool {
            return $entity instanceof ItemEntity && $entity->getItem()->canStackWith($item) && $entity->getItem()->getCount() + $item->getCount() <= $item->getMaxStackSize();
        });

        if (count($itemEntities) > 0) {
            $totalCount = $item->getCount();

            foreach ($itemEntities as $entity) {
                $totalCount += $entity->getItem()->getCount();
            }

            if ($totalCount <= $this->maxCapacity($item)) {
                $entity = $itemEntities[array_rand($itemEntities)];
                $entity->setStackSize($entity->getItem()->getCount() + $item->getCount());
            }
        } else {
            $itemEntity = new ItemEntity(Location::fromObject($this->location, $world, Utils::getRandomFloat() * 360), $item);
            $itemEntity->setPickupDelay(10);
            if ($this instanceof TeamGenerator) {
                $itemEntity->setMotion(new Vector3(Utils::getRandomFloat() * 0.2 - 0.1, 0.2, Utils::getRandomFloat() * 0.2 - 0.1));
            }
            $itemEntity->setCanDuplicate($multiplePlayers);
            $itemEntity->spawnToAll();
        }
    }

    public static function onGeneratorItemPickup(Player $player, Item $item): void
    {
        /** @var CQArena|null $arena */
        $arena = Conquests::getInstance()->getArena($player);
        if ($arena === null) {
            return;
        }

        $statsId = match ($item->getTypeId()) {
            ItemTypeIds::IRON_INGOT => StatsData::CQ_IRON_COLLECTED,
            ItemTypeIds::GOLD_INGOT => StatsData::CQ_GOLD_COLLECTED,
            ItemTypeIds::DIAMOND => StatsData::CQ_DIAMONDS_COLLECTED,
            ItemTypeIds::EMERALD => StatsData::CQ_EMERALDS_COLLECTED,
            default => null,
        };

        if ($statsId !== null) {
            $arena->getStatsData()->addValue($player, $statsId, $item->getCount());
        }

        $player->broadcastSound(new PopSound(2.2), [$player]);
    }

    abstract public function maxCapacity(Item $item): int;
}