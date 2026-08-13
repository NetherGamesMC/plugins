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
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use Throwable;
use function array_rand;
use function mt_rand;

class Sheep extends Animal
{
    use WalkEntityTrait;

    /** @var DyeColor */
    private DyeColor $color;
    /** @var bool */
    private bool $sheared;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setMaxHealth(8);
        $this->setHealth(8);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::SHEEP;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $colors = DyeColor::cases();
        try {
            $color = $nbt->getByte("Color", DyeColorIdMap::getInstance()->toId($colors[array_rand($colors)]));
        } catch (Throwable) {
            $color = $nbt->getShort("Color", DyeColorIdMap::getInstance()->toId($colors[array_rand($colors)]));
        }
        $this->color = DyeColorIdMap::getInstance()->fromId($color);

        $this->sheared = (bool)$nbt->getByte("Sheared", 0);
    }

    public function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setByte(EntityMetadataProperties::COLOR, DyeColorIdMap::getInstance()->toId($this->color));
        $properties->setGenericFlag(EntityMetadataFlags::SHEARED, $this->sheared);
    }

    public function getSheared(): bool
    {
        return $this->sheared;
    }

    public function setSheared(bool $sheared): void
    {
        $this->sheared = $sheared;
        $this->networkPropertiesDirty = true;
    }

    public function getName(): string
    {
        return 'Sheep';
    }

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        $drops = [
            ($this->isOnFire() ? VanillaItems::COOKED_MUTTON() : VanillaItems::RAW_MUTTON())->setCount(mt_rand(1, 2))
        ];

        if (!$this->sheared) {
            $drops[] = VanillaBlocks::WOOL()->setColor($this->color)->asItem();
        }

        return $drops;
    }

    public function getColor(): DyeColor
    {
        return $this->color;
    }

    public function setColor(DyeColor $color): void
    {
        $this->color = $color;
        $this->networkPropertiesDirty = true;
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

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setByte("Color", DyeColorIdMap::getInstance()->toId($this->color));
        $nbt->setByte("Sheared", $this->sheared ? 1 : 0);

        return $nbt;
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return $this->isBaby() ? 0 : mt_rand(1, 3);
        }

        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.3, 0.9);
    }
}
