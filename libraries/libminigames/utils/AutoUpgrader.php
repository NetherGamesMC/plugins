<?php

namespace libminigames\utils;

use Closure;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\EventPriority;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function array_filter;
use function count;
use const ARRAY_FILTER_USE_BOTH;

class AutoUpgrader
{
    use SingletonTrait;

    private function __construct()
    {
        $plugin = NGEssentials::getInstance();

        $plugin->getServer()->getPluginManager()->registerEvent(
            EntityItemPickupEvent::class, Closure::fromCallable([$this, 'onEntityItemPickup']),
            EventPriority::MONITOR, $plugin, false
        );
    }

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        $player = $event->getEntity();
        $targetItem = $event->getItem();

        if ($player instanceof Player) {
            $event->setItem($this->upgradeItem($player, $targetItem));
        }
    }

    /**
     * @param Player $player The player who is upgrading the item.
     * @param Item $targetItem The item that the player is trying to upgrade to.
     *
     * @return Item The leftover item
     */
    public function upgradeItem(Player $player, Item $targetItem): Item
    {
        if ($targetItem instanceof TieredTool) {
            if (($slot = $this->checkTieredTool($player, $targetItem)) !== null) {
                $playerInventory = $player->getInventory();
                $sourceItem = $playerInventory->getItem($slot);

                $playerInventory->setItem($slot, $targetItem);

                return $sourceItem;
            }
        } elseif ($targetItem instanceof Armor) {
            if ($this->checkArmor($player, $targetItem)) {
                $armorInventory = $player->getArmorInventory();
                $targetSlot = $targetItem->getArmorSlot();
                $sourceItem = $armorInventory->getItem($targetSlot);

                $armorInventory->setItem($targetSlot, $targetItem);

                return $sourceItem;
            }
        }

        return $targetItem;
    }

    /**
     * Checks if a given tieredTool is better than the one in the player's hotbar
     * returns the index of the tool to be replaced, else returns null
     */
    private function checkTieredTool(Player $player, TieredTool $targetTool): ?int
    {
        $inventory = $player->getInventory();
        $comparableItems = array_filter($inventory->getContents(), static function ($item, $slot) use ($inventory, $targetTool): bool {
            return $inventory->isHotbarSlot($slot) && $item instanceof TieredTool && $item->getBlockToolType() === $targetTool->getBlockToolType();
        }, ARRAY_FILTER_USE_BOTH);

        if (count($comparableItems) > 0) {
            /** @var int $slot */
            $slot = min(array_keys($comparableItems));
            $item = $comparableItems[$slot];

            if ($this->compareTierType($item, $targetTool) && $this->compareEnchantments($item, $targetTool)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Checks if the targetItem is better for the specific purpose than the sourceItem
     */
    private function compareTierType(TieredTool $sourceItem, TieredTool $targetItem): bool
    {
        $sourceItemTier = $sourceItem->getTier();
        $targetItemTier = $targetItem->getTier();

        if ($sourceItem instanceof Sword || $sourceItem instanceof Axe) {
            return $targetItemTier->getBaseAttackPoints() > $sourceItemTier->getBaseAttackPoints();
        }

        if ($sourceItem instanceof Hoe) {
            return $targetItemTier->getHarvestLevel() > $sourceItemTier->getHarvestLevel();
        }

        if ($sourceItem instanceof Pickaxe) {
            return $targetItemTier->getBaseEfficiency() > $sourceItemTier->getBaseEfficiency();
        }

        return $targetItemTier->getMaxDurability() > $sourceItemTier->getMaxDurability();
    }

    /**
     * Checks if the targetItem has better|the same enchantments than the sourceItem
     */
    private function compareEnchantments(Item $sourceItem, Item $targetItem): bool
    {
        if (count($enchantments = $sourceItem->getEnchantments()) > 0) {
            foreach ($enchantments as $enchantment) {
                $toolEnchantment = $targetItem->getEnchantment($enchantment->getType());

                if ($toolEnchantment === null || $toolEnchantment->getLevel() < $enchantment->getLevel()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if a given armor is better than the one in the player's armor inventory
     * returns true, if the armor is better, else returns false
     */
    private function checkArmor(Player $player, Armor $targetArmor): bool
    {
        $armorInventory = $player->getArmorInventory();
        $sourceArmor = $armorInventory->getItem($targetArmor->getArmorSlot());

        if ($targetArmor->getDefensePoints() <= $sourceArmor->getDefensePoints()) {
            return false;
        }

        return $this->compareEnchantments($sourceArmor, $targetArmor);
    }
}