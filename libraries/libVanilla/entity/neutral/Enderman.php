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

namespace libVanilla\entity\neutral;

use libVanilla\entity\ai\WalkEntityTrait;
use libVanilla\entity\Monster;
use libVanilla\entity\utils\EntitySizeUtils;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class Enderman extends Monster
{
    use WalkEntityTrait;

    /** @var Block */
    private Block $blockHeld;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->blockHeld = VanillaBlocks::AIR();

        $this->setMaxHealth(40);
        $this->setHealth(40);

        $this->setDamages([0, 4.5, 7, 10.5]);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ENDERMAN;
    }

    public function getName(): string
    {
        return 'Enderman';
    }

    public function interactTarget(): void
    {
        /** @var Entity $target */
        $target = $this->getTargetEntity();

        $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
        $target->attack($ev);

        $this->broadcastAnimation(new ArmSwingAnimation($this));
    }

    public function getBlockHeld(): Block
    {
        return $this->blockHeld;
    }

    public function setBlockHeld(Block $block): void
    {
        $this->blockHeld = $block;
        $this->networkPropertiesDirty = true;
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $typeConverter = $player->getNetworkSession()->getTypeConverter();
        $this->getNetworkProperties()->setInt(EntityMetadataProperties::ENDERMAN_HELD_ITEM_ID, $typeConverter->getBlockTranslator()->internalIdToNetworkId($this->blockHeld->getStateId()));
        $this->getNetworkProperties()->clearDirtyProperties(); //needed for multi protocol

        parent::sendSpawnPacket($player);
    }

    //public function syncNetworkData(EntityMetadataCollection $properties): void
    //{
    //    parent::syncNetworkData($properties);
    //
    //    $properties->setInt(EntityMetadataProperties::ENDERMAN_HELD_ITEM_ID, RuntimeBlockMapping::getInstance()->toRuntimeId(->getStateId()));
    //}

    /**
     * @return Item[]
     */
    public function getDrops(): array
    {
        $drops = [
            VanillaItems::ENDER_PEARL()->setCount(mt_rand(0, 1)),
        ];

        if ($this->blockHeld instanceof Air) {
            $drops[] = $this->blockHeld->asItem();
        }

        return $drops;
    }

    public function getXpDropAmount(): int
    {
        if (($lastDamage = $this->getLastDamageCause()) !== null && $lastDamage->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            return 5;
        }

        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return EntitySizeUtils::upright(2.6, 0.6);
    }
}
