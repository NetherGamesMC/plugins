<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\store\categories\Category;
use NetherGames\NGEssentials\player\store\categories\SWStore;
use pocketmine\player\Player;

class Store
{
    /** @var Category[] */
    private array $categories;

    public function __construct(private PlayerManager $manager)
    {
        $this->categories[SWStore::ID] = new SWStore($this);
    }


    public function getCategory(int $categoryId): Category
    {
        return $this->categories[$categoryId];
    }

    public function setValue(Player $player, int $categoryId, array $data = []): void
    {
        $value = $this->getValue($player);

        $value[$categoryId] = $data;

        $this->getPlugin()->getPlayerData()->setValue($player, PlayerData::STORE_DATA, $value);
    }

    public function getValue(Player $player, int $categoryId = -1): array
    {
        $value = $this->getPlugin()->getPlayerData()->getArray($player, PlayerData::STORE_DATA);

        if ($categoryId === -1) {
            return $value;
        }

        return $value[$categoryId] ?? [];
    }

    public function getPlugin(): NGEssentials
    {
        return $this->getManager()->getPlugin();
    }

    public function getManager(): PlayerManager
    {
        return $this->manager;
    }
}