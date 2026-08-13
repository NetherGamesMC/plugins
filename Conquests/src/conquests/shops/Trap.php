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
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\RegistryTrait;
use pocketmine\utils\TextFormat;

/**
 * @method static self HOOK()
 * @method static self FEATHER()
 * @method static self ALARM()
 * @method static self FATIGUE()
 */
final class Trap
{
    use RegistryTrait;

    protected static function setup(): void
    {
        self::register(
            registryName: "hook",
            name: "It's a trap!",
            description: "Inflicts Blindness and Slowness for 8 seconds.",
            item: VanillaBlocks::TRIPWIRE_HOOK()->asItem(),
            iconUrl: "textures/blocks/trip_wire_source",
            onActivate: function (Player $target): void {
                $target->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 8 * 20, 0));
                $target->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 8 * 20, 0));
            },
            friendly: false
        );
        self::register(
            registryName: "feather",
            name: "Counter-Offensive Trap",
            description: "Grants Speed II & Jump Boost II for 10 seconds to allied players near your base.",
            item: VanillaItems::FEATHER(),
            iconUrl: "textures/items/feather",
            onActivate: function (Player $target): void {
                $target->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 10 * 20, 0));
                $target->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 10 * 20, 0));
            },
            friendly: true
        );
        self::register(
            registryName: "alarm",
            name: "Alarm Trap",
            description: "Reveals invisible players as well as their name and team.",
            item: VanillaBlocks::REDSTONE_TORCH()->setLit()->asItem(),
            iconUrl: "textures/blocks/redstone_torch_on",
            onActivate: function (Player $target): void {
                /** @var NGPlayer $target */
                if ($target->getEffects()->has(VanillaEffects::INVISIBILITY())) {
                    $target->getEffects()->remove(VanillaEffects::INVISIBILITY());
                    $target->sendConditionalMessage(TextFormat::RED . "Your invisibility has been taken!");
                }
            },
            friendly: false
        );
        self::register(
            registryName: "fatigue",
            name: "Miner Fatigue Trap",
            description: "Inflict Mining Fatigue for 10 seconds.",
            item: VanillaItems::IRON_PICKAXE(),
            iconUrl: "textures/items/iron_pickaxe",
            onActivate: function (Player $target): void {
                $target->getEffects()->add(new EffectInstance(VanillaEffects::MINING_FATIGUE(), 10 * 20, 0));
            },
            friendly: false
        );
    }

    /** @var array<string, self> */
    private static array $nameAliasMapping = [];

    /**
     * @param Closure(Player): void $onActivate
     */
    private static function register(string $registryName, string $name, string $description, Item $item, string $iconUrl, Closure $onActivate, bool $friendly): void
    {
        $trap = new self($name, $description, $item, $iconUrl, $onActivate, $friendly);
        self::_registryRegister($registryName, $trap);
        self::$nameAliasMapping[strtolower($name)] = $trap;
    }

    /**
     * @param Closure(Player): void $onActivation
     */
    public function __construct(
        public readonly string   $name,
        public readonly string   $description,
        private readonly Item    $item,
        public readonly string   $iconUrl,
        private readonly Closure $onActivation,
        public readonly bool     $friendly
    )
    {
    }

    public function activate(Player $target): void
    {
        ($this->onActivation)($target);
    }

    public function asItem(): Item
    {
        return clone $this->item;
    }

    /**
     * @return array<self>
     */
    public static function getAll(): array
    {
        /** @var array<self> $result */
        $result = self::_registryGetAll();
        return $result;
    }

    public static function fromName(string $name): ?self
    {
        return self::$nameAliasMapping[strtolower($name)] ?? null;
    }
}