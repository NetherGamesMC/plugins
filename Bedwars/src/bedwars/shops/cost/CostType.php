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
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace bedwars\shops\cost;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\RegistryTrait;
use pocketmine\utils\TextFormat;

/**
 * @method static self EMERALD()
 * @method static self GOLD()
 * @method static self IRON()
 */
final class CostType
{
    use RegistryTrait;

    protected static function setup(): void
    {
        self::register("emerald", "emerald", TextFormat::DARK_GREEN, VanillaItems::EMERALD());
        self::register("gold", "gold ingot", TextFormat::MINECOIN_GOLD, VanillaItems::GOLD_INGOT());
        self::register("iron", "iron ingot", TextFormat::WHITE, VanillaItems::IRON_INGOT());
    }

    protected static function register(string $name, string $displayName, string $color, Item $material): void
    {
        self::_registryRegister($name, new self($name, $displayName, $color, $material));
    }

    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly string $color,
        private readonly Item  $material
    )
    {
    }

    public function asItem(int $amount): Item
    {
        $material = clone $this->material;
        return $material->setCount($amount);
    }

    /**
     * @return array{name: string, displayName: string, color: string}
     */
    public function getAttributes(): array
    {
        return [
            "name" => $this->name,
            "displayName" => $this->displayName,
            "color" => $this->color
        ];
    }

    /**
     * Returns true if the player has the provided amount of the cost type in their inventory
     */
    public function contains(Player $player, int $amount): bool
    {
        // the instance to check in the inventories against
        $instance = $this->asItem($amount);
        return $player->getInventory()->contains($instance);
    }

}