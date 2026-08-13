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

namespace libVanilla\features;

use Closure;
use libVanilla\entity\object\ThrownTrident;
use libVanilla\item\Trident;
use libVanilla\network\PacketHandler;
use libVanilla\network\PacketProcessor;
use libVanilla\VanillaPlugin;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\Trident as PMTrident;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use ReflectionClass;

class Tridents extends Feature implements PacketHandler
{
    private const IMPALING_DAMAGE = 2.5;

    /** @var array<int, bool> */
    private array $spinning = [];

    protected function setup(PluginBase $plugin): void
    {
        VanillaPlugin::ENCHANTS()->register($plugin);

        EntityFactory::getInstance()->register(ThrownTrident::class, function (World $world, CompoundTag $nbt): ThrownTrident {
            $itemTag = $nbt->getCompoundTag(ThrownTrident::TAG_ITEM);
            if ($itemTag === null) {
                throw new SavedDataLoadingException("Expected \"" . ThrownTrident::TAG_ITEM . "\" NBT tag not found");
            }

            $item = Item::nbtDeserialize($itemTag);
            if ($item->isNull()) {
                throw new SavedDataLoadingException("Trident item is invalid");
            }
            return new ThrownTrident(Helper::parseLocation($nbt, $world), $item, null, $nbt);
        }, [
            'minecraft:trident', //java
            'minecraft:thrown_trident', //bedrock
            'Trident', //backwards compat for people who used #4547 before it was merged, since it was sitting around for 4 years...
            'ThrownTrident' //as above
        ]);

        $pluginManager = $plugin->getServer()->getPluginManager();
        $pluginManager->registerEvent(PlayerQuitEvent::class, Closure::fromCallable([$this, "onQuit"]), EventPriority::HIGH, $plugin);
        $pluginManager->registerEvent(ProjectileLaunchEvent::class, Closure::fromCallable([$this, "onProjectileLaunch"]), EventPriority::LOW, $plugin);
        $pluginManager->registerEvent(EntityDamageByEntityEvent::class, Closure::fromCallable([$this, "onDamage"]), EventPriority::LOW, $plugin);

        PacketProcessor::getInstance()->registerHandler($this, $plugin);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        unset($this->spinning[$event->getPlayer()->getId()]);
    }

    public function handlePlayerAction(NetworkSession $source, PlayerActionPacket $packet): bool
    {
        if ($packet->action !== PlayerAction::START_SPIN_ATTACK && $packet->action != PlayerAction::STOP_SPIN_ATTACK) {
            return false;
        }
        $this->setSpinning($source->getPlayer(), $packet->action === PlayerAction::START_SPIN_ATTACK);

        return true;
    }

    private function setSpinning(Player $player, bool $newState): void
    {
        $playerId = $player->getId();
        $currentState = isset($this->spinning[$playerId]);
        if ($currentState === $newState) {
            return;
        }
        $player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SPIN_ATTACK, $newState);
        if (!$newState) {
            unset($this->spinning[$playerId]);
            self::updateBoundingBox($player, 1.8, 0.6);
        } else {
            $this->spinning[$playerId] = true;
            self::updateBoundingBox($player, 0.6, 0.6);
        }
    }

    private static function updateBoundingBox(Player $player, float $baseHeight, float $baseWidth): void
    {
        $baseHeight *= $player->getScale();
        $baseWidth *= $player->getScale();

        $player->size = new EntitySizeInfo($baseHeight, $baseWidth, $player->size->getEyeHeight());

        $refClass = new ReflectionClass($player);
        $refMeth = $refClass->getMethod("recalculateBoundingBox");
        $refMeth->invoke($player);

        $player->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $baseHeight);
        $player->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $baseWidth);
    }

    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof PMTrident) {
            $newEntity = new ThrownTrident($entity->getLocation(), $entity->getItem(), $entity->getOwningEntity(), $entity->saveNBT());
            $newEntity->spawnToAll();

            $entity->close();
        }
    }

    public function onDamage(EntityDamageByEntityEvent $event): void
    {
        $sourceItem = null;
        if ($event instanceof EntityDamageByChildEntityEvent) {
            // projectile hit
            $child = $event->getChild();
            if (!$child instanceof ThrownTrident) {
                return;
            }
            $sourceItem = $child->getItem();
        } else {
            $damager = $event->getDamager();
            if ($damager instanceof Player) {
                // todo: future mob AI changes would require this to use the Living class...
                //  there exists "InventoryHolder" but it does not guarantee we have a primary hand
                $sourceItem = $damager->getInventory()->getItemInHand();
            }
        }
        if (!$sourceItem instanceof Trident) {
            return;
        }
        $impaling = EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::IMPALING);

        $addedModifier = $event->getModifier(EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);
        $addedModifier += self::IMPALING_DAMAGE * $sourceItem->getEnchantmentLevel($impaling);
        $event->setModifier($addedModifier, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);
    }
}