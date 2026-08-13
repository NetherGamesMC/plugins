<?php
declare(strict_types=1);

namespace uhc\utils;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Location;
use pocketmine\item\Item;

class PlayerCache
{

    public function __construct(
        private string   $xuid,
        private Location $location,
        private float    $health,
        private int      $xp,
        /** @var array<int, Item> $armorContents */
        private array    $armorContents,
        /** @var array<int, Item> $inventoryContents */
        private array    $inventoryContents,
        /** @var EffectInstance[] $effects */
        private array    $effects
    )
    {
    }

    public function getXuid(): string
    {
        return $this->xuid;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getHealth(): float
    {
        return $this->health;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    /**
     * @return array<int, Item>
     */
    public function getArmorContents(): array
    {
        return $this->armorContents;
    }

    /**
     * @return array<int, Item>
     */
    public function getInventoryContents(): array
    {
        return $this->inventoryContents;
    }

    /**
     * @return EffectInstance[]
     */
    public function getEffects(): array
    {
        return $this->effects;
    }
}