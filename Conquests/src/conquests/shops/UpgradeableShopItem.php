<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace conquests\shops;

use Closure;
use conquests\CQTeam;
use conquests\shops\cost\CostType;
use conquests\utils\Utils;
use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class UpgradeableShopItem extends ShopItem
{
    /**
     * @param non-empty-list<ShopItem> $tiers
     * @param (Closure(static, Player, CQTeam): ?string)|null $purchaseValidatorFn
     * @param (Closure(static, Player, CQTeam): void)|null $onPrePurchase
     * @param (Closure(static, Player, CQTeam): void)|null $onPurchase
     */
    public function __construct(
        string                 $name,
        private readonly array $tiers,
        ?Closure               $purchaseValidatorFn = null,
        ?Closure               $onPrePurchase = null,
        ?Closure               $onPurchase = null,
        ShopInventorySlot      $slot = ShopInventorySlot::INVENTORY,
        bool                   $overrideInventory = false
    )
    {
        $previousTier = null;
        foreach ($tiers as $tier) {
            $tier->replaceFn = function (Item $item, Player $player, CQTeam $team) use ($previousTier): void {
                if ($previousTier !== null) {
                    /** @var Item $previousItem */
                    $previousItem = $previousTier->getValue();
                    $previousItem = $previousTier->itemModifierFn !== null ? ($previousTier->itemModifierFn)($previousItem, $player, $team) : $previousItem;

                    if (Utils::replaceItem($player, $previousItem, $item)) {
                        return;
                    }
                }

                $player->getInventory()->addItem($item);
            };

            $previousTier = $tier;
        }

        $baseTier = $tiers[0];
        parent::__construct($name, $baseTier->getValue(), $baseTier->getIconPath(), $baseTier->getCost(), $baseTier->getDescription(), $purchaseValidatorFn, null, $onPrePurchase, $onPurchase, $slot, $overrideInventory);
    }

    /**
     * @return non-empty-list<ShopItem>
     */
    public function getTiers(): array
    {
        return $this->tiers;
    }

    /**
     * Attempts to fetch the tier with the level provided & throws an exception if not found
     */
    public function getTier(int $level): ShopItem
    {
        return $this->tiers[$level] ?? throw new InvalidArgumentException("Tier $level is not a valid tier for shop item {$this->getName()}");
    }

    /**
     * Returns true if the item has a tier associated with the level provided
     */
    public function hasTier(int $level): bool
    {
        return isset($this->tiers[$level]);
    }

    /**
     * Returns true if a tier exists after the level provided
     */
    public function hasNextTier(int $level): bool
    {
        return $this->hasTier($level + 1);
    }

    /**
     * Returns the next tier after the level provided
     */
    public function getNextTier(int $level): ShopItem
    {
        if (!$this->hasNextTier($level)) {
            throw new RuntimeException("Shop item {$this->getName()} does not have a tier after $level");
        }
        return $this->getTier($level + 1);
    }

    public function handlePurchase(Player $player, CQTeam $team, ?int $multiplier = null): ?string
    {
        $level = $team->getShopItemLevel($player, $this);
        if ($level !== null && !$this->hasNextTier($level)) {
            return TextFormat::RED . "You have reached the maximum level for this item!";
        }

        $purchasedLevel = $level !== null ? $level + 1 : 0;
        $tier = $this->getTier($purchasedLevel);

        if (!$tier->canPurchase($player, $team, $multiplier)) {
            $costName = $this->cost->getName() . match ($this->cost->getType()) {
                    CostType::EMERALD() => "s",
                    default => ""
                };
            return TextFormat::RED . "You don't have enough $costName!";
        }

        if ($this->purchaseValidatorFn !== null && ($errorMessage = ($this->purchaseValidatorFn)($this, $player, $team)) !== null) {
            return $errorMessage;
        }

        if ($tier->purchaseValidatorFn !== null && ($errorMessage = ($tier->purchaseValidatorFn)($tier, $player, $team)) !== null) {
            return $errorMessage;
        }

        if ($this->onPrePurchase !== null) {
            ($this->onPrePurchase)($this, $player, $team);
        }

        $team->setPermanent(
            player: $player,
            key: $this->name,
            value: $purchasedLevel
        );

        $error = $tier->handlePurchase($player, $team, $multiplier);
        if ($error !== null) {
            return $error;
        }

        if ($this->onPurchase !== null) {
            ($this->onPurchase)($this, $player, $team);
        }
        return null;
    }

}