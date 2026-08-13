<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
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

namespace bedwars\shops\menu;

use bedwars\Bedwars;
use bedwars\BWArena;
use bedwars\BWTeam;
use bedwars\shops\Trap;
use bedwars\shops\Upgrade;
use bedwars\shops\Upgrader;
use bedwars\shops\UpgradeTier;
use bedwars\utils\TrapManager;
use bedwars\utils\Utils;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FireExtinguishSound;
use function count;
use function explode;
use function str_replace;

final class UpgraderMenu
{
    /** The name associated with this menu */
    public const MENU_IDENTIFIER = "bedwars:upgrader";

    /** These constants are used for item setting in the main menu */
    public const STARTING_INDEX = 9;
    public const BACK_BUTTON_INDEX = 22;

    /** These constants are used for item setting in trap tab of the menu */
    public const TRAP_MENU_INDEX = 22;
    public const TRAP_STARTING_INDEX = 30;

    /** This pattern is searched in the tiered text and replaced with the actual cost of the item */
    public const PRICE_SEARCH_PATTERN = "%price%";

    /**
     * These data tags are used to attach data about the shop items to an actual item.
     */
    public const UPGRADER_DATA_TAG = "upgraderData";
    public const TRAP_DATA_TAG = "trapData";
    protected const DATA_SEPARATOR = "|";
    /**
     * This tag value is used whenever an item in the upgrader or trap menus can't be upgraded or equipped (respectively)
     */
    public const EMPTY_DATA_TAG_VALUE = "-1";

    public function __construct(private Upgrader $upgrader)
    {
    }

    public function send(Player $player, BWTeam $team): void
    {
        $upgrader = InvMenu::create(self::MENU_IDENTIFIER);
        $inventory = $upgrader->getInventory();
        assert($inventory instanceof InvMenuInventory);

        // Show upgrades menu by default
        $this->sendUpgradesMenu($inventory, $player, $team);

        $upgrader->setName("Upgrades & Traps")->setListener(InvMenu::readonly($this->handleMenuTransaction(...)));
        $upgrader->send($player);
    }

    public function handleMenuTransaction(DeterministicInvMenuTransaction $transaction): void
    {
        $player = $transaction->getPlayer();
        $itemClicked = $transaction->getItemClicked();
        $action = $transaction->getAction();
        /** @var InvMenuInventory $inventory */
        $inventory = $action->getInventory();

        // If player isn't in an arena, don't show them this menu
        if (!($arena = Bedwars::getInstance()->getArena($player)) instanceof BWArena) {
            $player->removeCurrentWindow();
            return;
        }

        $team = $arena->getTeam($player);
        // TODO: this should be a lot more extendable & dynamic
        match (true) {
            // First check for directional buttons
            $itemClicked->equals(self::createBackButtonItem()) => $this->sendUpgradesMenu($inventory, $player, $team),
            $itemClicked->equals(self::createTrapMenuItem()) => $this->sendTrapMenu($inventory, $player, $team),
            // Then check if item is part of a submenu
            Utils::hasTag($itemClicked->getNamedTag(), self::UPGRADER_DATA_TAG, StringTag::class) => $this->handleUpgradeTransaction($transaction, $team),
            Utils::hasTag($itemClicked->getNamedTag(), self::TRAP_DATA_TAG, StringTag::class) => $this->handleTrapMenuTransaction($transaction, $team),
            // Otherwise, log a warning
            default => Bedwars::getInstance()->getLogger()->warning("Unhandled upgrader menu transaction for player {$player->getName()}")
        };
    }

    public function handleUpgradeTransaction(DeterministicInvMenuTransaction $transaction, BWTeam $team): void
    {
        /** @var NGPlayer $player */
        $player = $transaction->getPlayer();
        $itemClicked = $transaction->getItemClicked();
        $action = $transaction->getAction();
        /** @var InvMenuInventory $inventory */
        $inventory = $action->getInventory();
        if (($data = $itemClicked->getNamedTag()->getString(self::UPGRADER_DATA_TAG)) === self::EMPTY_DATA_TAG_VALUE) {
            $player->sendConditionalMessage(TextFormat::RED . "That upgrade is already maxed out!");
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return;
        }
        [$upgrade, $slot, $cost] = self::resolveUpgradeFromItemData($data);

        $costItem = VanillaItems::DIAMOND()->setCount($cost);
        if (!$player->getInventory()->contains($costItem)) {
            $player->sendConditionalMessage(TextFormat::RED . "You don't have enough diamonds!");
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return;
        }
        if ($this->getUpgrader()->upgrade($player, $team, $upgrade, $slot)) {
            $player->getInventory()->removeItem($costItem);
        }
        $this->sendUpgradesMenu($inventory, $player, $team);
    }

    public function handleTrapMenuTransaction(DeterministicInvMenuTransaction $transaction, BWTeam $team): void
    {
        /** @var NGPlayer $player */
        $player = $transaction->getPlayer();
        $itemClicked = $transaction->getItemClicked();
        $action = $transaction->getAction();
        /** @var InvMenuInventory $inventory */
        $inventory = $action->getInventory();
        $data = $itemClicked->getNamedTag()->getString(self::TRAP_DATA_TAG);

        if ($data === self::EMPTY_DATA_TAG_VALUE) {
            $player->sendConditionalMessage(TextFormat::RED . "The trap queue is currently full!");
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return;
        }

        /** @var Trap $trap */
        [$trap, $slot, $cost] = self::resolveTrapFromItemData($data);

        $itemCost = VanillaItems::DIAMOND()->setCount($cost);
        if (!$player->getInventory()->contains($itemCost)) {
            $player->sendConditionalMessage(TextFormat::RED . "You don't have enough diamonds!");
            $player->broadcastSound(new FireExtinguishSound(), [$player]);
            return;
        }
        if ($this->getUpgrader()->queue($player, $team, $trap, $slot)) {
            $player->getInventory()->removeItem($itemCost);
        }
        $this->sendTrapMenu($inventory, $player, $team);
    }

    public function sendTrapMenu(InvMenuInventory $inventory, Player $player, BWTeam $team): void
    {
        $index = self::STARTING_INDEX;
        $traps = $team->getTrapManager()->getTraps();

        $inventory->clearAll();
        foreach (Trap::getAll() as $trap) {
            $cost = $team->getTrapManager()->getNextTrapCost();
            $trapItem = $trap->asItem();
            $item = VanillaItems::DIAMOND()->setCount($cost);
            [$customName, $lore, $trapData] = match (true) {
                $team->getTrapManager()->isFull() => [
                    TextFormat::RESET . TextFormat::RED . $trap->name,
                    [TextFormat::RESET . TextFormat::GRAY . $trap->description, "", TextFormat::RESET . TextFormat::RED . "Trap queue full!"],
                    self::EMPTY_DATA_TAG_VALUE
                ],
                $player->getInventory()->contains($item) => [
                    TextFormat::RESET . TextFormat::YELLOW . $trap->name,
                    [TextFormat::RESET . TextFormat::GRAY . $trap->description, "", TextFormat::RESET . TextFormat::GRAY . "Cost: " . TextFormat::AQUA . "$cost Diamond" . ($cost === 1 ? "" : 's'), "", TextFormat::RESET . TextFormat::YELLOW . "Click to purchase!"],
                    self::asItemData($trap, count($traps) + 1, $cost),
                ],
                default => [
                    TextFormat::RESET . TextFormat::RED . $trap->name,
                    [TextFormat::RESET . TextFormat::GRAY . $trap->description, "", TextFormat::RESET . TextFormat::GRAY . 'Cost: ' . TextFormat::AQUA . "$cost Diamond" . ($cost === 1 ? "" : "s"), "", TextFormat::RESET . TextFormat::RED . "You don't have enough Diamonds!"],
                    self::asItemData($trap, count($traps) + 1, $cost),
                ]
            };

            $trapItem->setCustomName($customName);
            $trapItem->setLore($lore);
            $trapItem->setNamedTag($trapItem->getNamedTag()->setString(self::TRAP_DATA_TAG, $trapData));
            $inventory->setItem($index++, $trapItem);
        }

        $inventory->setItem(self::BACK_BUTTON_INDEX, self::createBackButtonItem());
    }

    public function sendUpgradesMenu(InvMenuInventory $inventory, Player $player, BWTeam $team): void
    {
        $inventory->clearAll();
        $index = self::STARTING_INDEX;
        foreach (Upgrade::getAll() as $upgrade) {
            $currentTierLevel = $team->getUpgradeLevel($upgrade);
            if ($upgrade->hasUpgrade($currentTierLevel)) {
                $nextTierLevel = $currentTierLevel + 1;
                $tier = $upgrade->getTier($nextTierLevel) ?? throw new AssumptionFailedError("Upgrade tier should not be null");
                $cost = $team->getArena()->isTriosOrSquads() ? $tier->teamCost : $tier->cost;
                $item = VanillaItems::DIAMOND()->setCount($cost);
                $contains = $player->getInventory()->contains($item);
                [$count, $customName, $lore, $itemData] = match (true) {
                    $upgrade->hasTiers() => [
                        // Setting the count of an item beyond its max stack level can cause issues with UI
                        $upgrade->asItem()->getMaxStackSize() !== 1 ? $nextTierLevel : 1,
                        TextFormat::RESET . ($contains ? TextFormat::YELLOW : TextFormat::RED) . $upgrade->getFormattedName($nextTierLevel),
                        [
                            TextFormat::RESET . TextFormat::GRAY . $upgrade->description,
                            "",
                            ...array_map(
                                fn(int $tierLevel, UpgradeTier $tier) => TextFormat::RESET . ($tierLevel <= $currentTierLevel ? TextFormat::GREEN : TextFormat::GRAY) . str_replace(
                                        search: self::PRICE_SEARCH_PATTERN,
                                        replace: (string)($team->getArena()->isTriosOrSquads() ? $tier->teamCost : $tier->cost),
                                        subject: $tier->tieredText
                                    ),
                                array_keys($upgrade->tiers),
                                $upgrade->tiers
                            ),
                            TextFormat::RESET . ($contains ? TextFormat::YELLOW . "Click to purchase!" : TextFormat::RED . "You don't have enough diamonds!")
                        ],
                        self::asItemData($upgrade, $nextTierLevel, $cost),
                    ],
                    default => [
                        1,
                        TextFormat::RESET . ($contains ? TextFormat::YELLOW : TextFormat::RED) . $upgrade->getFormattedName(),
                        [
                            TextFormat::RESET . TextFormat::GRAY . $upgrade->description,
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "Cost: " . TextFormat::AQUA . "$cost Diamonds",
                            "",
                            TextFormat::RESET . ($contains ? TextFormat::YELLOW . "Click to purchase!" : TextFormat::RED . "You don't have enough diamonds!")
                        ],
                        self::asItemData($upgrade, 1, $cost)
                    ]
                };
                $upgradeItem = $upgrade->asItem()->setCount($count);
                $upgradeItem->setCustomName($customName);
                $upgradeItem->setLore($lore);
                $upgradeItem->setNamedTag($upgradeItem->getNamedTag()->setString(self::UPGRADER_DATA_TAG, $itemData));
            } else {
                $currentLevel = $team->getUpgradeLevel($upgrade);
                $tier = $upgrade->getTier($currentLevel) ?? throw new AssumptionFailedError("Upgrade tier should not be null");
                $cost = $team->getArena()->isTriosOrSquads() ? $tier->teamCost : $tier->cost;
                [$level, $customName, $lore] = match (true) {
                    $upgrade->hasTiers() => [
                        $upgrade->asItem()->getMaxStackSize() !== 1 ? $currentLevel : 1,
                        TextFormat::RESET . TextFormat::GREEN . $upgrade->getFormattedName($currentLevel),
                        [
                            TextFormat::RESET . TextFormat::GRAY . $upgrade->description,
                            "",
                            ...array_map(
                                callback: fn(UpgradeTier $tier) => TextFormat::RESET . TextFormat::GREEN . str_replace(
                                        search: self::PRICE_SEARCH_PATTERN,
                                        replace: (string)($team->getArena()->isTriosOrSquads() ? $tier->teamCost : $tier->cost),
                                        subject: $tier->tieredText
                                    ),
                                array: $upgrade->tiers
                            ),
                            "",
                            TextFormat::RESET . TextFormat::GREEN . "UNLOCKED"
                        ]
                    ],
                    default => [
                        1,
                        TextFormat::RESET . TextFormat::GREEN . $upgrade->getFormattedName($currentLevel),
                        [
                            TextFormat::RESET . TextFormat::GRAY . $upgrade->description,
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "Cost: " . TextFormat::AQUA . "$cost Diamond" . ($cost === 1 ? "" : "s"),
                            "",
                            TextFormat::RESET . TextFormat::GREEN . "UNLOCKED"
                        ]
                    ]
                };
                $upgradeItem = $upgrade->asItem()->setCount($level);
                $upgradeItem->setCustomName($customName);
                $upgradeItem->setLore($lore);
                $upgradeItem->setNamedTag($upgradeItem->getNamedTag()->setString(self::UPGRADER_DATA_TAG, "-1"));
            }
            $inventory->setItem($index++, $upgradeItem);
        }

        $inventory->setItem(self::TRAP_MENU_INDEX, self::createTrapMenuItem());

        $traps = $team->getTrapManager()->getTraps();
        $cost = $team->getTrapManager()->getNextTrapCost();
        for ($index = 0; $index < TrapManager::MAX_QUEUED_TRAPS; $index++) {
            // Get the trap at the index
            $trap = $traps[$index] ?? null;
            // The slot is the user's associated index (1, 2, 3), rather than a zero-indexed version like (0, 1, 2)
            $slot = $index + 1;
            // Get the word associated with the slot
            $slotWord = Utils::getWordFromNumber($slot);
            // Attempt to get the trap's item (if it exists). Otherwise, use the empty slot item.
            $item = $trap?->asItem() ?? VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::LIGHT_GRAY)->asItem();
            // Set associated data
            $item->setCustomName(TextFormat::RESET . ($trap !== null ? TextFormat::GREEN : TextFormat::RED) . "Trap #$slot");
            $item->setLore([
                TextFormat::RESET . TextFormat::GRAY . "The $slotWord enemy to walk into your base will trigger this trap!",
                "",
                TextFormat::RESET . TextFormat::GRAY . "Purchasing a trap will queue it here. Its cost will scale based on the number of traps queued.",
                // If the trap is empty, show the cost. Otherwise, don't add extra lore
                ...($trap === null ? ["", TextFormat::RESET . TextFormat::GRAY . "Next trap: " . TextFormat::AQUA . "$cost Diamond" . ($cost === 1 ? "" : "s")] : [])
            ]);
            $inventory->setItem(self::TRAP_STARTING_INDEX + $index, $item);
        }
    }

    public function getUpgrader(): Upgrader
    {
        return $this->upgrader;
    }

    private static function createBackButtonItem(): Item
    {
        return VanillaItems::ARROW()->setCustomName(TextFormat::RESET . TextFormat::GREEN . "Go Back")->setLore([TextFormat::RESET . TextFormat::GRAY . "To Upgrades & Traps"]);
    }

    private static function createTrapMenuItem(): Item
    {
        return VanillaItems::LEATHER()->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Buy a trap")
            ->setLore([
                TextFormat::RESET . TextFormat::GRAY . "Purchased traps will be queued on the right.",
                "",
                TextFormat::RESET . TextFormat::YELLOW . "Click to browse!"
            ]);
    }

    /**
     * Converts an item, slot, and cost into a string that can be saved into an item's named tag.
     * @param int $associatedData - If the item is a trap, this is the trap's slot. If the item is an upgrade, this is the upgrade's level.
     */
    private static function asItemData(Trap|Upgrade $item, int $associatedData, int $cost): string
    {
        return implode(
            separator: self::DATA_SEPARATOR,
            array: [$item->name, (string)$associatedData, (string)$cost]
        );
    }

    /**
     * @param string $itemData
     * @return array{Trap, int, int}
     */
    private static function resolveTrapFromItemData(string $itemData): array
    {
        $data = explode(separator: self::DATA_SEPARATOR, string: $itemData);
        return [
            Trap::fromName($data[0]) ?? throw new AssumptionFailedError("Unable to locate trap with name $data[0]"),
            (int)$data[1],
            (int)$data[2]
        ];
    }

    /**
     * @param string $itemData
     * @return array{Upgrade, int, int}
     */
    private static function resolveUpgradeFromItemData(string $itemData): array
    {
        $data = explode(separator: self::DATA_SEPARATOR, string: $itemData);
        return [
            Upgrade::fromName($data[0]) ?? throw new AssumptionFailedError("Unable to locate upgrade with name $data[0]"),
            (int)$data[1],
            (int)$data[2]
        ];
    }
}