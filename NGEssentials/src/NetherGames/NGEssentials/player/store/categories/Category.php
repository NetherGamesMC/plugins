<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store\categories;


use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\store\Store;
use pocketmine\player\Player;

abstract class Category
{
    public const SELECTED = 0;
    public const CLAIMED = 1;

    public const ID = -1;

    public function __construct(private Store $store)
    {
    }

    abstract public function getName(): string;

    abstract public function getIcon(): string;

    public function getCurrency(Player $player): int
    {
        return $this->getStore()->getPlugin()->getPlayerData()->getInt($player, PlayerData::COINS);
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function reduceCurrency(Player $player, int $amount): void
    {
        $this->getStore()->getPlugin()->getPlayerData()->addInt($player, PlayerData::COINS, -$amount);
    }

    abstract public function getSelected(Player $player, int $subCategoryId): int;

    abstract public function getValue(Player $player, int $subCategoryId = -1): array;

    abstract public function setSelected(Player $player, int $subCategoryId, int $selected): void;

    abstract public function setValue(Player $player, int $subCategoryId, array $data): void;

    abstract public function sendForm(Player $player, ?callable $onBack = null): void;
}