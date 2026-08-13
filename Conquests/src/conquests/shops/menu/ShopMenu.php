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

namespace conquests\shops\menu;

use conquests\CQTeam;
use conquests\shops\Shop;
use conquests\shops\ShopCategory;
use conquests\shops\ShopItem;
use conquests\shops\UpgradeableShopItem;
use conquests\utils\Items;
use conquests\utils\Utils;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FireExtinguishSound;

final class ShopMenu
{
    /** The name associated with this menu */
    public const MENU_IDENTIFIER = "conquests:shop";
    private const MENU_TITLE = "Item Shop";

    private const STARTING_CATEGORY_SLOT_INDEX = 1;
    private const STARTING_CATEGORY_ITEMS_SLOT_INDEX = 19;
    private const CATEGORY_EDGE_SLOTS = [26 => true, 35 => true, 46 => true];

    private const QUICK_BUY_SLOT_INDEX = 49;
    private const QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX = 53;

    private const QUICK_BUY_SLOTS = [
        19, 20, 21, 22, 23, 24, 25,
        28, 29, 30, 31, 32, 33, 34,
        37, 38, 39, 40, 41, 42, 43
    ];

    /** This tag indicates the name of the shop item that this item belongs to */
    private const SHOP_ITEM_NAME_TAG = "shopName";
    /**
     * This tag indicates the category that this associated item belongs to.
     * This tag is a StringTag and is the name of the category.
     */
    private const SHOP_ITEM_CATEGORY_TAG = "shopCategory";

    /** This tag indicates the offset in the quick-buy menu that is to be assigned */
    public const QUICK_BUY_OFFSET_TAG = "quickBuyOffset";
    /** This tag is used to check if the item clicked was the quick-buy removal item  */
    public const QUICK_BUY_REMOVE_TAG = "removeQuickBuy";


    /**
     * Below represents the indexes  of how quick-buy items are stored in the player's data
     * Each quick-buy item is stored in the following format (the actual values are examples):
     * QUICK_BUY_OFFSET => [ITEM_QB_CATEGORY_ID => 2, ITEM_QB_CATEGORY_OFFSET => 5]
     *
     * The category ID represents the legacy ID of the category (@see ShopCategory::resolveLegacyIdToCategory(int))
     * The category offset represents the index at which the item in the QB category is located (e.g., 5th item in the category is at index 4)
     */
    private const ITEM_QB_CATEGORY_ID = 0;
    private const ITEM_QB_CATEGORY_OFFSET = 1;

    private QuickBuyState $quickBuyState = QuickBuyState::NORMAL;
    private int $quickBuyOffset = -1;

    public function __construct(
        private readonly Shop   $shop,
        private readonly Player $player,
        private readonly CQTeam $team,
    )
    {
    }

    public function send(): void
    {
        $shop = InvMenu::create(self::MENU_IDENTIFIER)->setName(self::MENU_TITLE);
        /** @var InvMenuInventory $inventory */
        $inventory = $shop->getInventory();
        $this->setupCategories($inventory);
        $this->setupQuickBuyItems($inventory);
        $shop->setListener(InvMenu::readonly($this->handleTransaction(...)));
        $shop->send($this->player);
    }

    public function handleTransaction(DeterministicInvMenuTransaction $transaction): void
    {
        $player = $transaction->getPlayer();
        $itemClicked = $transaction->getItemClicked();
        $action = $transaction->getAction();

        $arena = $this->team->getArena();

        if (!$arena->isInArena($player)) {
            $player->removeCurrentWindow();
            return;
        }
        /** @var InvMenuInventory $inventory */
        $inventory = $action->getInventory();
        // if item clicked is a category item, refresh the items in the category
        if (($category = $this->resolveCategoryFromDisplayItem($itemClicked)) !== null) {
            $inventory->clearAll();

            $this->setupCategories($inventory);
            $this->setupCategoryItems($inventory, $category);

            // If the player is changing the item category from "Remove Quick Buy Item" or
            // the player is changing back to "Quick Buy" category during "Quick Buy Item Selection"
            if ($this->quickBuyState === QuickBuyState::REMOVE_ITEM) {
                $this->quickBuyState = QuickBuyState::NORMAL;
            } else if ($this->quickBuyState === QuickBuyState::ADD_ITEM && $this->quickBuyOffset >= 0) {
                $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getQuickBuyAssignItem($this->quickBuyOffset));
            }
            return;
        }
        $processed = $this->processQuickBuy($transaction);

        // if the quick-buy logic failed & the item clicked was a shop item, attempt to purchase
        if (!$processed && self::resolveItemTagType($itemClicked) === ShopMenuTagType::SHOP_ITEM) {
            $this->purchase($itemClicked);

            $tag = $itemClicked->getNamedTag();
            if ($tag->getByte(ShopMenuTagType::QUICK_BUY_ITEM->value, 0) === 1) {
                $this->setupQuickBuyItems($inventory);
            } else {
                $this->setupCategoryItems($inventory, $this->shop->resolveCategoryFromName($tag->getString(self::SHOP_ITEM_CATEGORY_TAG)));
            }
        }
    }

    public function setupCategories(InvMenuInventory $inventory): void
    {
        $index = self::STARTING_CATEGORY_SLOT_INDEX;
        foreach ($this->shop->getCategories() as $category) {
            $item = $category->getDisplayItem()
                ->setCustomName(TextFormat::RESET . TextFormat::GREEN . $category->getName())
                ->setLore([TextFormat::RESET . TextFormat::YELLOW . "Click to view!"]);
            // tag it as a category display item
            $item->getNamedTag()->setString(ShopMenuTagType::CATEGORY_DISPLAY->value, $category->getName());
            if ($inventory->getItem($index)->equals($item)) {
                continue;
            }
            $inventory->setItem($index, $item);
            $index++;
        }
        $inventory->setItem(self::QUICK_BUY_SLOT_INDEX, Items::getQuickBuyItem());
    }

    /**
     * @param Player $player
     * @return array<int, array{category: ShopCategory, item: ShopItem}>
     */
    public function resolvePlayerQuickBuySlots(Player $player): array
    {
        $results = [];
        $gameSettings = NGEssentials::getInstance()->getPlayerData()->getGameSettings();

        // iterate over a player's quick buy items
        // the structure of the value is [categoryId, categoryItemIndex]
        foreach (
            $gameSettings->getArray($player, GameSettings::CQ_QUICK_BUY) as
            $offset => [self::ITEM_QB_CATEGORY_ID => $categoryId, self::ITEM_QB_CATEGORY_OFFSET => $itemIndex]
        ) {
            /** @var int $categoryId */
            /** @var int $itemIndex */

            $category = ShopCategory::resolveLegacyIdToCategory($categoryId);
            // if the category or the item doesn't exist, continue to the next iteration
            if ($category === null || ($item = $category->resolveItemFromIndex($itemIndex)) === null) {
                continue;
            }
            $results[(int)$offset] = ["category" => $category, "item" => $item];
        }
        return $results;
    }

    public function setupQuickBuyItems(InvMenuInventory $inventory): void
    {
        // the list of open slots for quick buy items
        $slots = self::QUICK_BUY_SLOTS;

        $quickBuyItems = $this->resolvePlayerQuickBuySlots($this->player);

        // { "7":[3,1],"14":[3,2],"1":[0,0],"8":[6,3],"0":[3,0],"2":[0,1],"15":[1,1],"9":[6,7] }
        foreach ($quickBuyItems as $index => ["category" => $category, "item" => $shopItem]) {
            $item = self::resolveDisplayItem($this->player, $this->team, $category, $shopItem);

            // Reset category key.
            $tag = $item->getNamedTag();
            // tag it as a quick buy item
            $tag->setByte(ShopMenuTagType::QUICK_BUY_ITEM->value, 1);
            $tag->setInt(ShopMenu::QUICK_BUY_OFFSET_TAG, $index);
            $item->setNamedTag($tag);

            // Now we set the item in inventory.
            $slot = self::QUICK_BUY_SLOTS[$index];
            if (!$inventory->getItem($slot)->equals($item)) {
                $inventory->setItem(self::QUICK_BUY_SLOTS[$index], $item);
            }

            $slots = array_diff($slots, [self::QUICK_BUY_SLOTS[$index]]);

            if (!$inventory->getItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX)->equalsExact(Items::getPreQuickBuyRemove())) {
                $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getPreQuickBuyRemove());
            }
        }

        foreach ($slots as $offset => $index) {
            $inventory->setItem($index, Items::getQuickBuyPanes($offset));
        }
    }

    public function setupCategoryItems(InvMenuInventory $inventory, ShopCategory $category): void
    {
        $index = self::STARTING_CATEGORY_ITEMS_SLOT_INDEX;
        foreach ($category->getItems() as $shopItem) {
            $item = self::resolveDisplayItem($this->player, $this->team, $category, $shopItem);

            $slot = $index++;
            if (isset(self::CATEGORY_EDGE_SLOTS[$slot])) {
                $slot += 2;
                $index = $slot + 1;
            }

            // don't overwrite the item if it's the same
            if ($inventory->getItem($slot)->equals($item)) {
                continue;
            }
            $inventory->setItem($slot, $item);
        }
    }

    private static function resolveDisplayItem(Player $player, CQTeam $team, ShopCategory $category, ShopItem $shopItem): Item
    {
        if ($shopItem instanceof UpgradeableShopItem) { // tiered tools
            $currentLevel = $team->getShopItemLevel($player, $shopItem);
            $shownLevel = match (true) {
                $currentLevel === null => 0,
                $shopItem->hasNextTier($currentLevel) => $currentLevel + 1,
                default => $currentLevel
            };
            $tier = $shopItem->getTier($shownLevel);
            $displayItem = $tier->getDisplayItem();
            $displayItem = $shopItem->itemModifierFn !== null ? ($shopItem->itemModifierFn)($displayItem, $player, $team) : $displayItem;

            $color = ($currentLevel !== null && !$shopItem->hasNextTier($currentLevel) || $tier->canPurchase($player, $team)) ? TextFormat::GREEN : TextFormat::RED;
            $displayItem->setCustomName(TextFormat::RESET . $color . $tier->getName());
            $displayItem->setLore([
                TextFormat::RESET . TextFormat::GRAY . "Cost: {$tier->getCost()->getDisplayName($team)}",
                TextFormat::RESET . TextFormat::GRAY . "Tier: " . TextFormat::YELLOW . Utils::getRomanNumber($shownLevel + 1),
                "",
                TextFormat::RESET . TextFormat::GRAY . "This is an upgradeable item.",
                TextFormat::RESET . TextFormat::GRAY . "It will lose 1 tier upon",
                TextFormat::RESET . TextFormat::GRAY . "death!",
                "",
                TextFormat::RESET . TextFormat::GRAY . "You will permanently",
                TextFormat::RESET . TextFormat::GRAY . "respawn with at least",
                TextFormat::RESET . TextFormat::GRAY . "the lowest tier.",
                "",
                TextFormat::RESET . match (true) {
                    $currentLevel !== null && !$shopItem->hasNextTier($currentLevel) => TextFormat::GREEN . "FULLY UPGRADED!",
                    $tier->canPurchase($player, $team) => TextFormat::GREEN . "Click to upgrade!",
                    default => TextFormat::RED . "You don't have enough {$shopItem->getPluralizedCostName()}}!"
                }
            ]);
        } else {
            $displayItem = $shopItem->getDisplayItem();
            $displayItem = $shopItem->itemModifierFn !== null ? ($shopItem->itemModifierFn)($displayItem, $player, $team) : $displayItem;

            $displayItem->setCustomName(TextFormat::RESET . ($shopItem->canPurchase($player, $team) ? TextFormat::GREEN : TextFormat::RED) . $shopItem->getName());
            $displayItem->setLore(match (true) {
                $shopItem->purchaseValidatorFn !== null && ($message = ($shopItem->purchaseValidatorFn)($shopItem, $player, $team)) !== null => [
                    "",
                    TextFormat::RESET . $message
                ],
                strlen($shopItem->getDescription()) > 0 => [
                    TextFormat::RESET . TextFormat::GRAY . "Cost: {$shopItem->getCost()->getDisplayName($team)}",
                    TextFormat::RESET . TextFormat::GRAY . $shopItem->getDescription(),
                    "",
                    TextFormat::RESET . ($shopItem->canPurchase($player, $team) ? TextFormat::YELLOW . "Click to purchase!" : TextFormat::RED . "You don't have enough {$shopItem->getPluralizedCostName()}!")
                ],
                default => [
                    TextFormat::RESET . TextFormat::GRAY . "Cost: {$shopItem->getCost()->getDisplayName($team)}",
                    "",
                    TextFormat::RESET . ($shopItem->canPurchase($player, $team) ? TextFormat::YELLOW . "Click to purchase!" : TextFormat::RED . "You don't have enough {$shopItem->getPluralizedCostName()}!")
                ]
            });
        }
        $tag = $displayItem->getNamedTag();
        // tag item as shop item
        $tag->setByte(ShopMenuTagType::SHOP_ITEM->value, 1);
        $tag->setString(self::SHOP_ITEM_NAME_TAG, $shopItem->getName());
        $tag->setString(self::SHOP_ITEM_CATEGORY_TAG, $category->getName());
        $displayItem->setNamedTag($tag);
        return $displayItem;
    }

    /**
     * Resolves a category display item to its related ShopCategory.
     */
    public function resolveCategoryFromDisplayItem(Item $item): ?ShopCategory
    {
        if (self::resolveItemTagType($item) === ShopMenuTagType::CATEGORY_DISPLAY) {
            foreach ($this->shop->getCategories() as $category) {
                if ($item->equals($category->getDisplayItem(), checkCompound: false)) {
                    return $category;
                }
            }
        }
        return null;
    }

    public function isQuickBuyItem(Item $item): bool
    {
        return $item->equalsExact(Items::getQuickBuyItem());
    }

    private static function resolveItemTagType(Item $item): ShopMenuTagType
    {
        foreach (ShopMenuTagType::cases() as $case) {
            if ($item->getNamedTag()->getTag($case->value) !== null) {
                return $case;
            }
        }
        return ShopMenuTagType::UNKNOWN;
    }

    /**
     * @return bool - Returns true if the selection was handled
     */
    private function processQuickBuy(DeterministicInvMenuTransaction $transaction): bool
    {
        $itemClicked = $transaction->getItemClicked();
        /** @var InvMenuInventory $inventory */
        $inventory = $transaction->getAction()->getInventory();

        // if the player clicked the quick buy item, set up the quick buy menu
        if ($this->isQuickBuyItem($itemClicked)) {
            $this->quickBuyState = QuickBuyState::NORMAL;
            $this->setupQuickBuyItems($inventory);
            return true;
        }

        // Check if the item is trying to remove an item from the quick buy
        if (Utils::hasTag($itemClicked->getNamedTag(), self::QUICK_BUY_REMOVE_TAG) || $this->quickBuyState === QuickBuyState::REMOVE_ITEM) {
            if ($this->quickBuyState !== QuickBuyState::REMOVE_ITEM) {
                $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getQuickBuyRemove());
                $this->quickBuyState = QuickBuyState::REMOVE_ITEM;
            } else if (self::resolveItemTagType($itemClicked) !== ShopMenuTagType::SHOP_ITEM) {
                // revert to normal state if the item clicked is not a shop item
                $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getPreQuickBuyRemove());
                $this->quickBuyState = QuickBuyState::NORMAL;
            } else {
                $gameSettings = NGEssentials::getInstance()->getPlayerData()->getGameSettings();
                $qbData = $gameSettings->getArray($this->player, GameSettings::CQ_QUICK_BUY);

                // remove the item from the quick buy & update it in their settings
                unset($qbData[$itemClicked->getNamedTag()->getInt(self::QUICK_BUY_OFFSET_TAG)]);
                $gameSettings->setValue($this->player, GameSettings::CQ_QUICK_BUY, $qbData);

                // re-setup the quick buy items
                $this->setupQuickBuyItems($inventory);
                // if the player has no more quick buy items, revert to normal state
                if (count($this->resolvePlayerQuickBuySlots($this->player)) !== 0) {
                    $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getQuickBuyRemove());
                } else {
                    $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getPreQuickBuyRemove());
                    $this->quickBuyState = QuickBuyState::NORMAL;
                }
            }
            return true;
        }

        // Check if the player is trying to assign a quick buy item
        if (self::resolveItemTagType($itemClicked) === ShopMenuTagType::QUICK_BUY_EMPTY_SLOT || $this->quickBuyState === QuickBuyState::ADD_ITEM && $this->quickBuyOffset >= 0) {
            $inventory->clearAll();

            $showQuickBuy = true;
            if ($this->quickBuyState === QuickBuyState::NORMAL) {
                $offset = $itemClicked->getNamedTag()->getInt(self::QUICK_BUY_OFFSET_TAG);

                $inventory->setItem(self::QUICK_BUY_REMOVE_OR_ASSIGN_SLOT_INDEX, Items::getQuickBuyAssignItem($offset));

                $showQuickBuy = false;
                $this->quickBuyState = QuickBuyState::ADD_ITEM;
                $this->quickBuyOffset = $offset;
            } else {
                if (self::resolveItemTagType($itemClicked) === ShopMenuTagType::SHOP_ITEM) {
                    $gameSettings = NGEssentials::getInstance()->getPlayerData()->getGameSettings();
                    /** @var array<int, array{0: int, 1: int}> $qbData */
                    $qbData = $gameSettings->getArray($this->player, GameSettings::CQ_QUICK_BUY);

                    // Replace the same entries.
                    $tag = $itemClicked->getNamedTag();
                    $category = $this->shop->resolveCategoryFromName($tag->getString(self::SHOP_ITEM_CATEGORY_TAG));
                    $sameItems = array_filter(
                        array: $qbData,
                        callback: fn(array $rows): bool => // TODO:
                            $rows[self::ITEM_QB_CATEGORY_ID] === $category->resolveCategoryToIndex() &&
                            $rows[self::ITEM_QB_CATEGORY_OFFSET] === $category->resolveItemToIndex($tag->getString(self::SHOP_ITEM_NAME_TAG))
                    );

                    unset($qbData[$this->quickBuyOffset], $qbData[array_key_first($sameItems)]);


                    $qbData[$this->quickBuyOffset] = [
                        self::ITEM_QB_CATEGORY_ID => $category->resolveCategoryToIndex(),
                        self::ITEM_QB_CATEGORY_OFFSET => $category->resolveItemToIndex($tag->getString(self::SHOP_ITEM_NAME_TAG))
                    ];

                    $gameSettings->setValue($this->player, GameSettings::CQ_QUICK_BUY, $qbData);
                }
                $this->quickBuyState = QuickBuyState::NORMAL;
            }

            $this->setupCategories($inventory);
            if ($showQuickBuy) {
                $this->setupQuickBuyItems($inventory);
            } else {
                $this->setupCategoryItems($inventory, $this->shop->getDefaultCategory());
            }
            return true;
        }
        return false;
    }

    /**
     * Attempts to purchase the shop item based on the item clicked.
     * Returns the category to navigate to if the purchase was successful or null otherwise.
     */
    public function purchase(Item $itemClicked): void
    {
        /** @var NGPlayer $player */
        $player = $this->player;
        $category = $this->shop->resolveCategoryFromName($itemClicked->getNamedTag()->getString(self::SHOP_ITEM_CATEGORY_TAG));
        $shopItem = $category->resolveItemFromName($itemClicked->getNamedTag()->getString(self::SHOP_ITEM_NAME_TAG));
        $error = $shopItem->handlePurchase($player, $this->team);

        if ($error !== null) {
            $player->sendConditionalMessage(TextFormat::RED . $error);
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return;
        }

        $player->playSound("random.orb");
    }
}