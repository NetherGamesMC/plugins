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

namespace bedwars\shops\form;

use bedwars\BWTeam;
use bedwars\shops\Shop;
use bedwars\shops\ShopCategory;
use bedwars\shops\ShopItem;
use bedwars\shops\SlideableShopItem;
use bedwars\shops\UpgradeableShopItem;
use bedwars\utils\Utils;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\elements\StepSlider;
use libforms\FormManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_keys;
use function array_map;
use function array_sum;
use function range;

final class ShopForm
{
    public const SHOP_TITLE = "Item Shop";

    public function __construct(private readonly Shop $shop)
    {
    }

    public function send(Player $player, BWTeam $team): void
    {
        $form = FormManager::createSimpleForm($player);
        if ($form === null) {
            return;
        }

        $form->setTitle(self::SHOP_TITLE);
        foreach ($this->shop->getCategories() as $category) {
            $form->addButton(new ImageButton(
                text: $category->getName(),
                imageType: ImageButton::IMAGE_TYPE_PATH,
                imageSource: $category->getIconPath(),
                callable: function (Player $player) use ($team, $category): void {
                    $this->sendCategory($player, $team, $category);
                }
            ));
        }

        $form->addButton(new ImageButton(TextFormat::RED . TextFormat::BOLD . "Exit", ImageButton::IMAGE_TYPE_PATH, "textures/blocks/barrier"));
        $form->sendForm();
    }

    public function sendCategory(Player $player, BWTeam $team, ShopCategory $category, string $message = ""): void
    {
        $form = FormManager::createSimpleForm($player);
        if ($form === null) {
            return;
        }

        $form->setTitle($category->getName());
        $form->setContent($message);

        foreach ($category->getItems() as $shopItem) {
            $form->addButton($this->createButtonFromShopItem($player, $team, $category, $shopItem));
        }
        $form->addButton(new ImageButton(
            text: TextFormat::RED . TextFormat::BOLD . "Back",
            imageType: ImageButton::IMAGE_TYPE_PATH,
            imageSource: "textures/blocks/barrier",
            callable: function (Player $player) use ($team) {
                $this->send($player, $team);
            }
        ));
        $form->sendForm();
    }

    public function createButtonFromShopItem(Player $player, BWTeam $team, ShopCategory $category, ShopItem $shopItem): Button
    {
        switch (true) {
            case $shopItem instanceof UpgradeableShopItem:
                $currentLevel = $team->getShopItemLevel($player, $shopItem);
                if ($currentLevel !== null && !$shopItem->hasNextTier($currentLevel)) {
                    $tier = $shopItem->getTier($currentLevel);

                    return new ImageButton(
                        text: TextFormat::RED . $tier->getName() .
                        TextFormat::EOL . "Tier: " . TextFormat::YELLOW . Utils::getRomanNumber($currentLevel + 1) . TextFormat::GRAY . " | " . TextFormat::RED . "Fully upgraded",
                        imageType: ImageButton::IMAGE_TYPE_PATH,
                        imageSource: $tier->getIconPath(),
                        callable: function (Player $player) use ($team, $category) {
                            /** @var NGPlayer $player */
                            $player->sendConditionalMessage(TextFormat::RED . "You've already fully upgraded this item!");
                            $this->sendCategory($player, $team, $category, TextFormat::RED . "Failed! " . TextFormat::RESET . "Item fully upgraded");
                        }
                    );
                }

                $shownLevel = $currentLevel !== null ? $currentLevel + 1 : 0;
                $tier = $shopItem->getTier($shownLevel);
                $purchaseColor = $tier->canPurchase($player, $team) ? TextFormat::GREEN : TextFormat::RED;

                return new ImageButton(
                    text: $purchaseColor . $tier->name . TextFormat::EOL .
                    TextFormat::GRAY . "Cost: {$tier->getCost()->getDisplayName($team)}" . TextFormat::GRAY . " | Tier: " . TextFormat::YELLOW . Utils::getRomanNumber($currentLevel + 1),
                    imageType: ImageButton::IMAGE_TYPE_PATH,
                    imageSource: $tier->getIconPath(),
                    callable: function (Player $player) use ($team, $category, $shopItem, $tier) {
                        $errorMessage = $this->shop->purchase($player, $team, $shopItem);
                        $this->sendCategory(
                            player: $player,
                            team: $team,
                            category: $category,
                            message: $errorMessage === null ?
                                TextFormat::GREEN . "Success! " . TextFormat::RESET . "You bought $tier->name" :
                                TextFormat::RED . "Failed! " . TextFormat::RESET . $errorMessage
                        );
                    }
                );
            default:
                $value = $shopItem->getValue();
                $displayName = $value instanceof Item && $value->getCount() !== 1 ? "{$value->getCount()}x {$value->getName()}" : $shopItem->getName();
                $purchaseColor = $shopItem->canPurchase($player, $team) ? TextFormat::GREEN : TextFormat::RED;
                ["amount" => $costAmount] = $shopItem->getCost()->getAttributes($team);

                if ($shopItem->purchaseValidatorFn !== null && ($message = ($shopItem->purchaseValidatorFn)($shopItem, $player, $team)) !== null) {
                    return new ImageButton(
                        text: $purchaseColor . $displayName . TextFormat::EOL . TextFormat::RED . $message,
                        imageType: ImageButton::IMAGE_TYPE_PATH,
                        imageSource: $shopItem->getIconPath(),
                        callable: function (Player $player) use ($team, $category, $message) {
                            /** @var NGPlayer $player */
                            $player->sendConditionalMessage($message);
                            $this->sendCategory($player, $team, $category, TextFormat::RED . "Failed! " . TextFormat::RESET . $message);
                        }
                    );
                }

                return new ImageButton(
                    text: $purchaseColor . $displayName . TextFormat::EOL . TextFormat::GRAY . "Cost: {$shopItem->getCost()->getDisplayName($team)}",
                    imageType: ImageButton::IMAGE_TYPE_PATH,
                    imageSource: $shopItem->getIconPath(),
                    callable: function (Player $player) use ($team, $category, $shopItem, $costAmount) {
                        // only send the slider form if the player has the setting enabled and the shop item supports it
                        if ($shopItem instanceof SlideableShopItem && NGEssentials::getInstance()->getPlayerData()->getGameSettings()->getBool($player, GameSettings::BW_SHOP_SLIDER)) {
                            // sum the counts of the cost type
                            $currentBalance = (int)array_sum(array_map(
                                callback: fn(Item $current) => $current->getCount(),
                                array: $player->getInventory()->all($shopItem->getCost()->asItem($team))
                            ));

                            // only send the slider form if more than one step can be displayed
                            if ($currentBalance >= $costAmount * 2 && $costAmount !== 0) {
                                $this->sendSliderForm($player, $team, $category, $shopItem, $costAmount, $currentBalance);
                                return;
                            }
                        }

                        $errorMessage = $this->shop->purchase($player, $team, $shopItem);
                        $this->sendCategory(
                            player: $player,
                            team: $team,
                            category: $category,
                            message: $errorMessage === null ?
                                TextFormat::GREEN . "Success! " . TextFormat::RESET . "You bought {$shopItem->getName()}" :
                                TextFormat::RED . "Failed! " . TextFormat::RESET . $errorMessage
                        );
                    }
                );
        }
    }

    public function sendSliderForm(Player $player, BWTeam $team, ShopCategory $category, ShopItem $shopItem, int $cost, int $balance): void
    {
        $form = FormManager::createCustomForm($player, function (Player $player) use ($team, $category): void {
            $this->sendCategory($player, $team, $category);
        });
        if ($form === null) {
            return;
        }
        $form->setTitle($category->getName());
        $steps = array_map(
            callback: fn(int $step) => (string)($shopItem->getCount() * ($step + 1)),
            array: array_keys(range($cost, $balance, $cost))
        );

        $form->addElement(new StepSlider("Amount: ", $steps, 0, function (Player $player, int $step) use ($team, $shopItem, $category) {
            $multiplier = $step + 1;
            $adjusted = $multiplier * $shopItem->getCost()->getAmountByGameType($team);
            // add the item count if the amount is greater than 1
            $itemDisplayName = ($multiplier !== 1 ? "{$adjusted}x " : "") . $shopItem->getName();

            $errorMessage = $this->shop->purchase($player, $team, $shopItem, $multiplier);
            $this->sendCategory(
                player: $player,
                team: $team,
                category: $category,
                message: $errorMessage === null ?
                    TextFormat::GREEN . "Success! " . TextFormat::RESET . "You bought $itemDisplayName" :
                    TextFormat::RED . "Failed! " . TextFormat::RESET . $errorMessage
            );
        }, true));

        $form->sendForm();
    }
}