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
 * @author Drew, Driesboy
 *
 */
declare(strict_types=1);

namespace libVanilla\entity\passive;

use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Animal;
use libVanilla\sound\MilkSound;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use function mt_rand;

class Cow extends Animal
{
    use WalkEntityTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(10);
        $this->setHealth(10);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::COW;
    }

    public function getName(): string
    {
        return 'Cow';
    }

    public function isHarvestableWith(Item $item): bool
    {
        return $item->getTypeId() === ItemTypeIds::BUCKET;
    }

    public function getHarvestItem(): Item
    {
        return VanillaItems::MILK_BUCKET();
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        $inventory = $player->getInventory();
        $stack = $inventory->getItemInHand();

        if ($this->isHarvestableWith($stack)) {
            $stack->pop();

            $item = $this->getHarvestItem();
            $player->getWorld()->addSound($clickPos->add(0.5, 0.5, 0.5), new MilkSound());

            if ($player->hasFiniteResources()) {
                if ($stack->getCount() === 0) {
                    $player->getInventory()->setItemInHand($item);
                } else {
                    $player->getInventory()->setItemInHand($stack);
                    $player->getInventory()->addItem($item);
                }
            } else {
                $player->getInventory()->addItem($item);
            }

            return true;
        }

        return parent::onInteract($player, $clickPos);
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        return [
            VanillaItems::LEATHER()->setCount(mt_rand(0, 2)),
            ($this->isOnFire() ? VanillaItems::STEAK() : VanillaItems::RAW_BEEF())->setCount(mt_rand(1, 3))
        ];
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return $this->isBaby() ? 0 : mt_rand(1, 3);
        }

        return 0;
    }

    /**
     * @return int[]
     */
    public function getBreedingItems(): array
    {
        return [
            ItemTypeIds::WHEAT
        ];
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.3, 0.9);
    }
}
