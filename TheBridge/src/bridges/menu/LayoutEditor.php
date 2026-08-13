<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123, larryTheCoder
 *
 */
declare(strict_types=1);

namespace bridges\menu;

use bridges\BridgeArena;
use bridges\BridgeSettings;
use bridges\TheBridge;
use bridges\utils\InvMenuListenerUtils;
use bridges\utils\Items;
use bridges\utils\KitManager;
use Closure;
use GlobalLogger;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function in_array;

class LayoutEditor
{
    /**
     * Inverse of PlayerInventory -> InvMenuInventory
     */
    private const HOTBAR_TO_PRESET = [
        36, 37, 38, 39, 40, 41, 42, 43, 44,

        0, 1, 2, 3, 4, 5, 6, 7, 8,
        9, 10, 11, 12, 13, 14, 15, 16, 17,
        18, 19, 20, 21, 22, 23, 24, 25, 26
    ];

    /**
     * The layout editor, the player can interact and touch these items.
     */
    private const PRESET_WHITELIST_MENU = [
        0, 1, 2, 3, 4, 5, 6, 7, 8,
        9, 10, 11, 12, 13, 14, 15, 16, 17,
        18, 19, 20, 21, 22, 23, 24, 25, 26,

        36, 37, 38, 39, 40, 41, 42, 43, 44,
        48, 50,
    ];

    /**
     * These IDs can NEVER be changed, only be added to!
     */
    public const SWORD_INDEX = 0;
    public const BOW_INDEX = 1;
    public const PICKAXE_INDEX = 2;
    public const TERRACOTTA_INDEX_1 = 3;
    public const TERRACOTTA_INDEX_2 = 4;
    public const GOLDEN_APPLE_INDEX = 5;
    public const ARROW_INDEX = 6;
    public const AMOUNT = self::ARROW_INDEX + 1;

    // Default arena parameters when not in arena
    private const DEFAULT_KIT_TYPE = BridgeSettings::KIT_NORMAL;
    private const DEFAULT_DYE_COLOR = DyeColor::RED;
    private const DEFAULT_NO_BOW_COOLDOWN = false;
    private const DEFAULT_INSTANT_BREAK_PICKAXE = false;

    // Item stack sizes
    private const EDIT_MODE_STACK_SIZE = 1;
    private const NORMAL_STACK_SIZE = 64;

    // Default inventory slot positions
    private const DEFAULT_SWORD_SLOT = 0;
    private const DEFAULT_BOW_SLOT = 1;
    private const DEFAULT_PICKAXE_SLOT = 2;
    private const DEFAULT_TERRACOTTA_1_SLOT = 3;
    private const DEFAULT_TERRACOTTA_2_SLOT = 4;
    private const DEFAULT_GOLDEN_APPLE_SLOT = 7;
    private const DEFAULT_ARROW_SLOT = 8;

    // Arena parameter keys
    private const PARAM_ARENA = 'arena';
    private const PARAM_KIT_TYPE = 'kitType';
    private const PARAM_DYE_COLOR = 'dyeColor';
    private const PARAM_NO_BOW_COOLDOWN = 'noBowCooldown';
    private const PARAM_INSTANT_BREAK_PICKAXE = 'instantBreakPickaxe';

    public static function sendPresetMenu(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(TextFormat::BLACK . "Inventory Preferences");

        /** @var InvMenuInventory $inventory */
        $inventory = $menu->getInventory();
        self::setContents($player, $inventory);

        // Scrapped from my TradePlus plugin
        $menu->setListener(self::handleInventoryListener($player, $inventory));
        $menu->send($player);
    }

    private static function setContents(Player $player, InvMenuInventory $inventory, bool $forceReset = false): void
    {
        $inventory->clearAll();

        $item = Items::getInventorySeparator();

        foreach ([27, 28, 29, 30, 31, 32, 33, 34, 35] as $index) {
            $inventory->setItem($index, $item);
        }

        $inventory->setItem(50, Items::getResetLayout());
        $inventory->setItem(48, Items::getSaveLayout());

        // Hotbar -> InvMenu preset change.
        $items = self::getContents($player, $forceReset, true);
        foreach (self::HOTBAR_TO_PRESET as $rawIndex => $realIndex) {
            if (!isset($items[$rawIndex])) {
                continue;
            }

            $inventory->setItem($realIndex, $items[$rawIndex]);
        }
    }

    /**
     * Get arena-related parameters for kit management
     *
     * @param Player $player
     * @return array{arena: BridgeArena|null, kitType: string, dyeColor: DyeColor, noBowCooldown: bool, instantBreakPickaxe: bool}
     */
    private static function getArenaParameters(Player $player): array
    {
        /** @var BridgeArena|null $arena */
        $arena = TheBridge::getInstance()->getArena($player);

        return [
            self::PARAM_ARENA => $arena,
            self::PARAM_KIT_TYPE => $arena?->getGameSettings()->getKit() ?? self::DEFAULT_KIT_TYPE,
            self::PARAM_DYE_COLOR => $arena?->getTeamNull($player)?->getDyeColor() ?? self::DEFAULT_DYE_COLOR,
            self::PARAM_NO_BOW_COOLDOWN => $arena?->getGameSettings()->hasNoBowCooldown() ?? self::DEFAULT_NO_BOW_COOLDOWN,
            self::PARAM_INSTANT_BREAK_PICKAXE => $arena?->getGameSettings()->hasInstantBreakPickaxe() ?? self::DEFAULT_INSTANT_BREAK_PICKAXE,
        ];
    }

    /**
     * Function gives all items with the right color indexed by the ids above.
     * Used only for validation in saveLayout().
     *
     * @param Player $player
     * @param bool $editMode
     * @return Item[]
     */
    private static function getItems(Player $player, bool $editMode): array
    {
        $params = self::getArenaParameters($player);
        $dyeColor = $params[self::PARAM_DYE_COLOR];
        $noBowCooldown = $params[self::PARAM_NO_BOW_COOLDOWN];
        $instantBreakPickaxe = $params[self::PARAM_INSTANT_BREAK_PICKAXE];

        // Always use Normal kit for layout editor
        return KitManager::getKitItems(
            BridgeSettings::KIT_NORMAL,
            $dyeColor,
            $noBowCooldown,
            $instantBreakPickaxe,
            $editMode ? self::EDIT_MODE_STACK_SIZE : self::NORMAL_STACK_SIZE
        );
    }

    /**
     * Get the contents of a player The Bridge inventory preferences.
     *
     * @param Player $player
     * @param bool $forceReset
     * @param bool $editMode
     * @return Item[] The items that is in order explicitly to the inventory.
     */
    public static function getContents(Player $player, bool $forceReset = false, bool $editMode = false): array
    {
        $params = self::getArenaParameters($player);
        $kitType = $params[self::PARAM_KIT_TYPE];
        $dyeColor = $params[self::PARAM_DYE_COLOR];
        $noBowCooldown = $params[self::PARAM_NO_BOW_COOLDOWN];
        $instantBreakPickaxe = $params[self::PARAM_INSTANT_BREAK_PICKAXE];

        // For non-customizable kits, return fixed positions
        if ($kitType !== BridgeSettings::KIT_NORMAL && $kitType !== BridgeSettings::KIT_OVERPOWERED) {
            return self::getFixedKitLayout(
                $kitType,
                $dyeColor,
                $noBowCooldown,
                $instantBreakPickaxe
            );
        }

        // For Normal and Overpowered kits, use saved layout
        $data = NGEssentials::getInstance()->getPlayerData()->getGameSettings()->getArray($player, GameSettings::TB_LAYOUT);

        if (LegacyLayoutConverter::isLegacyFormat($data)) {
            $data = LegacyLayoutConverter::convert($data);

            if ($data === null) {
                $forceReset = true;
            } else {
                NGEssentials::getInstance()->getPlayerData()->getGameSettings()->setValue($player, GameSettings::TB_LAYOUT, $data);
            }
        }

        // Get items based on actual kit type (Normal or Overpowered)
        $items = KitManager::getKitItems(
            $kitType,
            $dyeColor,
            $noBowCooldown,
            $instantBreakPickaxe,
            $editMode ? self::EDIT_MODE_STACK_SIZE : self::NORMAL_STACK_SIZE
        );

        $itemCount = count($items);

        if (!$forceReset && count($data) === $itemCount && $itemCount > 0) {
            /** @var Item[] $indexDuplicates */
            $indexDuplicates = [];
            $inventory = new SimpleInventory($player->getInventory()->getSize());

            foreach ($items as $index => $item) {
                if (!isset($data[$index])) {
                    continue;
                }

                $selectedIndex = $data[$index];

                if ($inventory->isSlotEmpty($selectedIndex)) {
                    $inventory->setItem($selectedIndex, $item);
                } else {
                    $indexDuplicates[] = $item;
                }
            }

            $inventory->addItem(...$indexDuplicates);

            return $inventory->getContents();
        }

        // Default layout for Normal/Overpowered kits
        $defaultLayout = [];
        foreach ($items as $index => $item) {
            $slot = match ($index) {
                self::SWORD_INDEX => self::DEFAULT_SWORD_SLOT,
                self::BOW_INDEX => self::DEFAULT_BOW_SLOT,
                self::PICKAXE_INDEX => self::DEFAULT_PICKAXE_SLOT,
                self::TERRACOTTA_INDEX_1 => self::DEFAULT_TERRACOTTA_1_SLOT,
                self::TERRACOTTA_INDEX_2 => self::DEFAULT_TERRACOTTA_2_SLOT,
                self::GOLDEN_APPLE_INDEX => self::DEFAULT_GOLDEN_APPLE_SLOT,
                self::ARROW_INDEX => self::DEFAULT_ARROW_SLOT,
                default => null,
            };

            if ($slot !== null) {
                $defaultLayout[$slot] = $item;
            }
        }

        return $defaultLayout;
    }

    /**
     * Get fixed layout for non-customizable kits
     *
     * @param string $kitType
     * @param DyeColor $teamColor
     * @param bool $noBowCooldown
     * @param bool $instantBreakPickaxe
     * @return Item[]
     */
    private static function getFixedKitLayout(
        string   $kitType,
        DyeColor $teamColor,
        bool     $noBowCooldown,
        bool     $instantBreakPickaxe
    ): array
    {
        $kitItems = KitManager::getKitItems(
            $kitType,
            $teamColor,
            $noBowCooldown,
            $instantBreakPickaxe,
            self::NORMAL_STACK_SIZE
        );

        $layout = [];
        $slot = 0;
        foreach ($kitItems as $item) {
            $layout[$slot] = $item;
            $slot++;
        }

        return $layout;
    }

    private static function handleInventoryListener(Player $player, InvMenuInventory $inventory): Closure
    {
        return InvMenuListenerUtils::multiple(
            InvMenuListenerUtils::whitelistSlots(self::PRESET_WHITELIST_MENU),
            self::handleTransaction($player, $inventory)
        );
    }

    private static function handleTransaction(Player $player, InvMenuInventory $menu): Closure
    {
        return static function (InvMenuTransaction $transaction) use ($player, $menu): InvMenuTransactionResult {
            $inventories = $transaction->getTransaction()->getInventories();
            $slot = $transaction->getAction()->getSlot();

            $isInvMenu = array_filter($inventories, static fn(Inventory $inventory) => !$inventory instanceof PlayerInventory);

            $itemInMenu = $transaction->getItemClicked();
            if ($itemInMenu->equals(Items::getResetLayout())) {
                self::setContents($player, $menu, true);
            } else if ($itemInMenu->equals(Items::getSaveLayout())) {
                self::saveLayout($player);
            }

            // Discard anything aside PlayerInventory and for
            // Mobile inventory array, check if the count is only 1.
            if (in_array($slot, self::HOTBAR_TO_PRESET, true) && (count($isInvMenu) === 2 || count($inventories) === 1)) {
                return $transaction->continue();
            }

            return $transaction->discard();
        };
    }

    /**
     * Save inventory layout for this player.
     *
     * @param Player $player
     */
    private static function saveLayout(Player $player): void
    {
        /** @var InvMenuInventory|null $window */
        $window = $player->getCurrentWindow();
        if ($window === null) {
            $player->sendMessage(TextFormat::RED . "Something went wrong while trying to save your layout. Try again later.");
            return;
        }

        $preferences = self::eliminateDangledItems($window);
        $payload = [];

        foreach ($preferences as $preferredIndex => $item) {
            $typeId = $item->getTypeId();

            $originalIndex = match ($typeId) {
                VanillaItems::IRON_SWORD()->getTypeId(),
                VanillaItems::DIAMOND_SWORD()->getTypeId(),
                VanillaItems::STICK()->getTypeId() => self::SWORD_INDEX,
                VanillaItems::BOW()->getTypeId() => self::BOW_INDEX,
                VanillaItems::DIAMOND_PICKAXE()->getTypeId() => self::PICKAXE_INDEX,
                VanillaBlocks::STAINED_CLAY()->asItem()->getTypeId() => (isset($payload[self::TERRACOTTA_INDEX_1]) ? self::TERRACOTTA_INDEX_2 : self::TERRACOTTA_INDEX_1),
                VanillaItems::GOLDEN_APPLE()->getTypeId() => self::GOLDEN_APPLE_INDEX,
                VanillaItems::ARROW()->getTypeId() => self::ARROW_INDEX,
                default => null,
            };

            if ($originalIndex === null) {
                GlobalLogger::get()->alert("Unhandled inventory transaction with id: " . $item->getName());
            } else {
                $payload[$originalIndex] = $preferredIndex;
            }
        }

        /** @var NGPlayer $player */
        if (count($payload) === count(self::getItems($player, true))) {
            $player->playSound('random.orb');
            NGEssentials::getInstance()->getPlayerData()->getGameSettings()->setValue($player, GameSettings::TB_LAYOUT, $payload);
            $player->sendMessage(TextFormat::GREEN . "Successfully updated your inventory preferences for The Bridge!");
        } else {
            $player->playSound('mob.villager.no');
            $player->sendMessage(TextFormat::RED . "Your contents were corrupted and have been reset to the last preset.");
        }
    }

    /**
     * @param InvMenuInventory $inventory
     * @return Item[]
     */
    private static function eliminateDangledItems(InvMenuInventory $inventory): array
    {
        $contents = $inventory->getContents();
        $contents = array_filter($contents, static function (Item $item): bool {
            return !($item->equals(Items::getResetLayout()) || $item->equals(Items::getSaveLayout()) || $item->equals(Items::getInventorySeparator()));
        });

        $results = [];
        foreach ($contents as $slot => $value) {
            $slotNum = array_search($slot, self::HOTBAR_TO_PRESET, true);

            $results[$slotNum] = $value;
        }

        return $results;
    }
}