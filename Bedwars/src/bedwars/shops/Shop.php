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

namespace bedwars\shops;

use bedwars\BWTeam;
use bedwars\shops\form\ShopForm;
use bedwars\shops\menu\ShopMenu;
use InvalidArgumentException;
use pocketmine\player\Player;
use function array_key_first;
use function microtime;

final class Shop
{
    /** @var ShopCategory[] */
    private array $categories;

    private ShopForm $form;
    /** @var array<int, float> - A mapping of player IDs to the last time their respective shop UI was opened */
    private array $lastOpening = [];


    public function __construct()
    {
        $this->form = new ShopForm($this);
        // Initialize shop categories from registry
        $this->categories = ShopCategory::getAll();
    }

    public function send(Player $player, BWTeam $team, bool $chestUI): void
    {
        if (!$this->canOpen($player)) {
            return;
        }

        if ($chestUI) {
            $menu = new ShopMenu(shop: $this, player: $player, team: $team);
            $menu->send();
        } else {
            // todo: all forms & menus should be stateful to a player
            $this->form->send($player, $team);
        }
        $this->lastOpening[$player->getId()] = microtime(true);
    }

    public function canOpen(Player $player): bool
    {
        return ($this->lastOpening[$player->getId()] ?? 0.0) < microtime(true) - 0.5;
    }

    public function onQuit(Player $player): void
    {
        unset($this->lastOpening[$player->getId()]);
    }

    /**
     * @return ShopCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getDefaultCategory(): ShopCategory
    {
        return $this->categories[array_key_first($this->categories)];
    }

    /**
     * While this method seems redundant, items from a category in the registry may not match the currently used one.
     * This may occur if the category is modified during runtime (e.g., prices are changed).
     */
    public function resolveCategory(ShopCategory $category): ShopCategory
    {
        return $this->resolveCategoryFromName($category->getName());
    }

    public function resolveCategoryFromName(string $name): ShopCategory
    {
        $found = array_filter(
            array: $this->categories,
            callback: fn(ShopCategory $category) => $category->getName() === $name
        );
        if (count($found) === 0) {
            throw new InvalidArgumentException("Unable to locate category from name $name");
        }
        return $found[array_key_first($found)];
    }

    public function purchase(Player $player, BWTeam $team, ShopItem $shopItem, ?int $multiplier = null): ?string
    {
        return $shopItem->handlePurchase($player, $team, $multiplier);
    }
}