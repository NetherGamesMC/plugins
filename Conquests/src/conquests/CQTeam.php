<?php
/**
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

namespace conquests;

use Closure;
use conquests\generators\TeamGenerator;
use conquests\shops\ShopCategory;
use conquests\shops\Upgrade;
use conquests\shops\UpgradeableShopItem;
use conquests\utils\entity\BridgeEgg;
use conquests\utils\entity\flag\BaseFlagEntity;
use conquests\utils\entity\Landmine;
use conquests\utils\entity\mob\MiniSkeleton;
use conquests\utils\StatsData;
use conquests\utils\TrapManager;
use conquests\utils\Utils;
use libminigames\Team;
use libminigames\TeamArena;
use NetherGames\NGEssentials\entity\custom\EntityNPC;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\utils\DyeColor;
use pocketmine\color\Color;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_filter;
use function array_flip;
use function count;
use function in_array;
use function str_replace;
use function strtoupper;

/**
 * @phpstan-type PermanentValue Item[]|bool|int
 * @phpstan-type PermanentValueList array<int|string, PermanentValue>
 */
class CQTeam extends Team
{
    public const ARMOR = "armor";
    public const SHEARS = "shears";
    public const PICKAXE = "Pickaxes";
    public const AXE = "Axes";

    /** @var array<string, array<int, Item>> */
    private array $playerEnderChestContents = [];
    /** @var TeamGenerator|null */
    private ?TeamGenerator $generator = null;
    /**
     * A mapping of the available upgrade names to the current tier
     * @var array<string, int>
     */
    private array $upgrades = [];
    /** @var array<string, PermanentValueList> - A mapping of player names to their associated permanent value lists */
    private array $permanent = [];
    /** @var TrapManager */
    private TrapManager $trapManager;
    /** @var BaseFlagEntity[] */
    private array $flags = [];
    /** @var int */
    private int $score = 0;

    /** @var array<int, Landmine> */
    private array $landmines = [];
    /** @var array<string, array<BridgeEgg>> */
    private array $bridgeEggs = [];
    /** @var array<string, int> */
    private array $spawnedMobs = [];

    public function getTrapManager(): TrapManager
    {
        return $this->trapManager;
    }

    public function setupTeam(World $world): void
    {
        $arena = $this->getArena();
        $arenaConfig = $arena->getPlugin()->getArenaConfig();

        $this->generator = new TeamGenerator(Location::fromObject($arenaConfig->getTeamGenerator($arena, $this->getId()), $world), $this);
        $this->trapManager = new TrapManager($this);

        $this->spawnShopkeeper(
            $arenaConfig->getTeamShop($arena, $this->getId(), $world),
            TextFormat::AQUA . 'ITEM SHOP' . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . 'RIGHT CLICK',
            static function (Player $player, CQTeam $team, bool $chestUI) use ($arena) {
                $arena->getShop()->send($player, $team, $chestUI);
            }
        );

        $this->spawnShopkeeper(
            $arenaConfig->getTeamUpgrader($arena, $this->getId(), $world),
            TextFormat::AQUA . strtoupper($arena->getPlugin()->getModeName($arena->getModeId())) . TextFormat::EOL . TextFormat::AQUA . 'UPGRADES' . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . 'RIGHT CLICK',
            static function (Player $player, CQTeam $team, bool $chestUI) use ($arena) {
                $arena->getUpgrader()->send($player, $team, $chestUI);
            }
        );
    }

    /**
     * @param Closure(Player, CQTeam, bool): void $callable
     * @return void
     */
    private function spawnShopkeeper(Location $location, string $name, Closure $callable): void
    {
        $arena = $this->getArena();
        $ess = $arena->getPlugin()->getEssentials();
        $gameSettings = $ess->getPlayerData()->getGameSettings();

        $onClick = static function (Player $player) use ($arena, $gameSettings, $callable) {
            if (!$arena->isSpectator($player) && ($team = $arena->getTeamNull($player)) !== null) {
                $chestUI = $gameSettings->getBool($player, GameSettings::CQ_CHEST_UI);
                $callable($player, $team, $chestUI);
            }
        };

        if (($entityId = CosmeticHandler::SHOPKEEPERS()->get($this->getPlayers())) === null) {
            $shop = new HumanNPC($location, $name, $arena->getSkin(), $onClick);
        } else {
            $shop = new EntityNPC($location, $name, $entityId, $onClick);
        }

        $shop->setAllowLeftClick(false);
        $ess->getEntityManager()->addEntity($shop);
    }

    /**
     * @return CQArena
     */
    public function getArena(): TeamArena
    {
        /** @var CQArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function getMaxScore(): int
    {
        /** @var CQArena $arena */
        $arena = $this->getArena();
        return $arena->getGameSettings()->getScoreAmount();
    }

    /**
     * @param Player $player
     * @return array{0: Armor, 1: Armor, 2: Armor, 3: Armor}
     */
    public function getPermanentArmor(Player $player): array
    {
        $helmet = VanillaItems::LEATHER_CAP();
        $chestplate = VanillaItems::LEATHER_TUNIC();

        [$r, $g, $b] = Utils::textFormatToRGB($this->getColor());
        $color = new Color($r, $g, $b);

        $helmet->setCustomColor($color);
        $chestplate->setCustomColor($color);

        /** @var ?Item[] $armor */
        $armor = $this->fetchPermanentValue($player, self::ARMOR);

        if ($armor !== null) {
            /** @var Armor $leggings */
            /** @var Armor $boots */
            [ArmorInventory::SLOT_LEGS => $leggings, ArmorInventory::SLOT_FEET => $boots] = $armor;
        } else {
            $leggings = VanillaItems::LEATHER_PANTS();
            $boots = VanillaItems::LEATHER_BOOTS();

            $leggings->setCustomColor($color);
            $boots->setCustomColor($color);
        }

        if (($level = $this->getUpgradeLevel(Upgrade::ARMOR())) > 0) {
            $helmet->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
            $chestplate->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
            $leggings->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
            $boots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $level));
        }

        if (($level = $this->getUpgradeLevel(Upgrade::SOFT_BOOTS())) > 0) {
            $boots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), $level));
        }

        $helmet->setUnbreakable();
        $chestplate->setUnbreakable();
        $leggings->setUnbreakable();
        $boots->setUnbreakable();

        return [$helmet, $chestplate, $leggings, $boots];
    }

    public function addSpawnedMob(string $mobId): void
    {
        $this->spawnedMobs[$mobId] = ($this->spawnedMobs[$mobId] ?? 0) + 1;
    }

    public function removeSpawnedMob(string $mobId): void
    {
        if (isset($this->spawnedMobs[$mobId])) {
            $this->spawnedMobs[$mobId]--;

            if ($this->spawnedMobs[$mobId] <= 0) {
                unset($this->spawnedMobs[$mobId]);
            }
        }
    }

    public function canSpawnMob(): bool
    {
        $totalCost = 0;

        foreach ($this->spawnedMobs as $mobId => $count) {
            $totalCost += $mobId === MiniSkeleton::getNetworkTypeId() ? $count / 5 : $count;
        }

        return $totalCost < 10;
    }

    /**
     * @param Player $player
     * @param bool $death
     *
     * @return Item[]
     */
    public function getPermanentTools(Player $player, bool $death = false): array
    {
        $items = [];
        foreach ([self::PICKAXE, self::AXE, self::SHEARS] as $toolType) {
            /** @var ?int $tier */
            $tier = $this->fetchPermanentValue($player, $toolType);
            if ($tier !== null) {
                $category = $this->getArena()->getShop()->resolveCategory(ShopCategory::TOOLS());
                /** @var UpgradeableShopItem $toolShopItem */
                $toolShopItem = $category->resolveItemFromName($toolType);

                // reduce player's tier upon death
                if ($death) {
                    $tier = max(0, $tier - 1);
                    $this->setPermanent($player, $toolType, $tier);
                }

                $toolTier = $toolShopItem->getTier($tier);
                /** @var Item $item */
                $item = $toolTier->getValue();
                if ($toolTier->itemModifierFn !== null) {
                    $item = ($toolTier->itemModifierFn)($item, $player, $this);
                }

                $items[] = $item;
            }
        }
        return $items;
    }

    public function getShopItemLevel(Player $player, UpgradeableShopItem $shopItem): ?int
    {
        /** @var int|null $level */
        $level = $this->fetchPermanentValue($player, $shopItem->name);
        return $level;
    }

    /**
     * @param Player $player
     * @param array-key $key
     * @param PermanentValue $value
     */
    public function setPermanent(Player $player, int|string $key, mixed $value): void
    {
        // ensure permanent items are initialized
        $this->permanent[$player->getName()] ??= [];
        $this->permanent[$player->getName()][$key] = $value;
    }

    /**
     * Attempts to fetch a permanent value attached to the player or null if not found.
     * @param array-key $key
     * @return PermanentValueList|null
     */
    public function fetchPermanentValue(Player $player, int|string $key): mixed
    {
        // ensure permanent items are initialized
        $this->permanent[$player->getName()] ??= [];
        /** @var PermanentValueList|null $item */
        $item = $this->permanent[$player->getName()][$key] ?? null;
        return $item;
    }

    /**
     * Gets the current level of the given upgrade, or 0 if not purchased.
     */
    public function getUpgradeLevel(Upgrade $upgrade): int
    {
        return $this->upgrades[$upgrade->name] ??= ($this->getArena()->getGameSettings()->hasMaxedUpgrades() ? count($upgrade->tiers) : 0);
    }

    public function setUpgradeLevel(Upgrade $upgrade, int $value): void
    {
        $this->upgrades[$upgrade->name] = $value;
    }

    public function canPlaceBlock(Vector3 $pos): bool
    {
        $arena = $this->getArena();
        $world = $arena->getWorld();
        $arenaConfig = $arena->getPlugin()->getArenaConfig();

        return match (true) {
            $arenaConfig->getTeamGenerator($arena, $this->getId())->distanceSquared($pos) < 9,
                $arenaConfig->getTeamShop($arena, $this->getId(), $world)->distanceSquared($pos) < 4,
                $arenaConfig->getTeamUpgrader($arena, $this->getId(), $world)->distanceSquared($pos) < 4,
                $arenaConfig->getTeamSpawn($arena, $this->getId())->distanceSquared($pos) < 25 => false,
            default => true,
        };
    }

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        parent::removePlayer($player, $teamChange);

        $arena = $this->getArena();
        if ($arena->isRunning() && !$arena->isSpectator($player)) {
            if (($damager = $arena->getLatestActiveHitter($player)) !== null) {
                $damagerTeam = $arena->getTeam($damager);

                $arena->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$this->getPlayerName($player), $damagerTeam->getPlayerName($damager)], $arena->getPlugin()->getRandomKillMessage(1, true)), true);
                $arena->addKill($damager, $player);
            }

            $arena->getPlugin()->generateDrop($player, null, true);

            foreach ($player->getEnderInventory()->getContents() as $item) {
                if (in_array($item->getTypeId(), [ItemTypeIds::IRON_INGOT, ItemTypeIds::GOLD_INGOT, ItemTypeIds::DIAMOND, ItemTypeIds::EMERALD], true)) {
                    $this->getGenerator()?->dropItem($item, false);
                }
            }
        }
    }

    public function getGenerator(): ?TeamGenerator
    {
        return $this->generator;
    }

    /**
     * @param Player $player
     * @param array<int, Item> $contents
     */
    public function setEnderChestContents(Player $player, array $contents): void
    {
        $this->playerEnderChestContents[$player->getName()] = array_filter(
            array: $contents,
            callback: static fn(Item $item): bool => !in_array($item->getTypeId(), [ItemTypeIds::IRON_INGOT, ItemTypeIds::GOLD_INGOT, ItemTypeIds::DIAMOND, ItemTypeIds::EMERALD], true)
        );
    }

    /**
     * @return BaseFlagEntity[]
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * Returns whether the player is near any team flag spawn point.
     * @param Player $player
     * @param float $distance Threshold block distance.
     * @return bool
     */
    public function isNearFlagSpawn(Player $player, float $distance = 10.0): bool
    {
        foreach ($this->getFlags() as $flag) {
            if ($flag->getSpawn()->distance($player->getLocation()) <= $distance) {
                return true;
            }
        }

        return false;
    }

    public function reconnectPlayer(Player $player, bool $reconnect = true): void
    {
        $arena = $this->getArena();
        if ($reconnect) {
            $this->addPlayer($player);
        }
        $player->setNameTag($this->getPlayerName($player, true));
        $arena->setPlayerJoinTime($player);

        $arena->broadcastMessage($this->getPlayerName($player) . TextFormat::GRAY . ($reconnect ? ' reconnected' : ' connected'), true);

        if (isset($this->playerEnderChestContents[$player->getName()])) {
            $player->getEnderInventory()->setContents($this->playerEnderChestContents[$player->getName()]);
        }

        /** @var NGPlayer $player */
        $player->setEnergized();
        $this->spawnPlayer($player, false);

        $arena->getScoreboard()->addPlayer($player);
        $this->sendScoreboard($player);
    }

    public function spawnPlayer(NGPlayer $player, bool $respawn): void
    {
        $arena = $this->getArena();
        $player->setHealthTag();

        $gameSettings = $this->getArena()->getGameSettings();
        if ($gameSettings->hasSpeed()) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 1, false));
        }

        if ($gameSettings->hasJumpBoost()) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), Limits::INT32_MAX, 2, false));
        }

        if (($level = $this->getUpgradeLevel(Upgrade::MINER())) > 0) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::HASTE(), Limits::INT32_MAX, $level - 1, false));
        }
        $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 2 * 20, 0, false));
        $player->getArmorInventory()->setContents($this->getPermanentArmor($player));

        if (!$gameSettings->hasKeepInventory() || !$respawn) {
            $sword = VanillaItems::WOODEN_SWORD();
            $sword->setUnbreakable();
            if (($swordUpgradeLevel = $this->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $swordUpgradeLevel));
            }
            $player->getInventory()->addItem($sword);

            foreach ($this->getPermanentTools($player, true) as $tool) {
                $player->getInventory()->addItem($tool);
            }
        }

        $player->teleport($arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, $this->getId()));
        $player->setGamemode(GameMode::SURVIVAL);
    }

    public function sendScoreboard(?Player $player = null): void
    {
        $arena = $this->getArena();
        $arena->getScoreboard()->setLines($player === null ? $this->getPlayers() : [$player], [
            13 => '',
            12 => CustomIcon::HOURGLASS,
            11 => '',
            10 => $this->getScoreVisuals(),
            9 => $arena->getOtherTeam($this)->getScoreVisuals(),
            8 => '',
            7 => CustomIcon::KILLS . TextFormat::GREEN . ($player === null ? 0 : $arena->getStatsData()->getValue($player, StatsData::CQ_KILLS)),
            6 => CustomIcon::FORTRESS . TextFormat::GREEN . ($player === null ? 0 : $arena->getStatsData()->getValue($player, StatsData::CQ_FLAGS_CAPTURED)),
            5 => '',
            4 => CustomIcon::MAP . TextFormat::GREEN . $arena->getMapDisplayName(),
            3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $arena->getPlugin()->getModeName($arena->getModeId()),
            2 => '',
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
        ]);
    }

    public function getScoreVisuals(): string
    {
        $goals = $this->getScore();
        $visual = ($this->getId() === Team::RED ? CustomIcon::FLAG_RED : CustomIcon::FLAG_BLUE) . ' ';

        for ($i = 1; $i <= $this->getMaxScore(); $i++) {
            if ($i <= $goals) {
                $visual .= $this->getColor() . '●';
            } else {
                $visual .= TextFormat::GRAY . '●';
            }
        }

        $isFlagStolen = false;
        foreach ($this->getFlags() as $flag) {
            if (!$flag->isAtSpawn()) {
                $isFlagStolen = true;
            }

            if ($flag->getOwningEntity() !== null) {
                return $visual . ' ' . CustomIcon::EMERGENCY;
            }
        }

        if ($isFlagStolen) {
            return $visual . ' ' . CustomIcon::WARNING;
        }

        return $visual;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    public function spawnFlag(): void
    {
        $plugin = $this->getArena()->getPlugin();

        foreach ($plugin->getArenaConfig()->getFlagSpawnsForTeam($arena = $this->getArena(), $this->getId()) as $vector3) {
            $this->flags[] = $plugin->getFlagFactory()->getFlag(Location::fromObject($vector3, $arena->getWorld()), $this);
        }
    }

    public function getDyeColor(): DyeColor
    {
        $map = [
            Team::WHITE => DyeColor::WHITE,
            Team::DARK_AQUA => DyeColor::CYAN,
            Team::YELLOW => DyeColor::YELLOW,
            Team::GREEN => DyeColor::GREEN,
            Team::DARK_BLUE => DyeColor::BLUE,
            Team::RED => DyeColor::RED,
            Team::DARK_GRAY => DyeColor::GRAY,
            Team::LIGHT_PURPLE => DyeColor::PINK,
            Team::GOLD => DyeColor::ORANGE,
            Team::GRAY => DyeColor::LIGHT_GRAY,
            Team::AQUA => DyeColor::LIGHT_BLUE,
            Team::DARK_PURPLE => DyeColor::PURPLE,
        ];

        return $map[$this->getTextFormatIndex()];
    }

    private function getTextFormatIndex(): int
    {
        return (array_flip(Team::COLORS)[$this->getColor()]) ?? 0;
    }

    public function increaseScore(Player $player): void
    {
        $this->score++;

        $arena = $this->getArena();
        $otherTeam = $arena->getOtherTeam($this);
        $scoreText = $this->getColor() . $this->getScore() . TextFormat::GRAY . ' - ' . $otherTeam->getColor() . $otherTeam->getScore();
        $arena->broadcastTitle($scoreText, $player->getNameTag() . " captured a flag!", 0, 120);

        if ($this->score >= $this->getMaxScore()) {
            $arena->finished = true;
        }

        $this->updateScoreboard();
    }

    public function updateScoreboard(): void
    {
        $arena = $this->getArena();
        $scoreboard = $arena->getScoreboard();

        $scoreboard->setLine($this->getPlayers(), 10, $this->getScoreVisuals());
        $scoreboard->setLine($arena->getOtherTeam($this)->getPlayers(), 9, $this->getScoreVisuals());
    }

    public function onLandminePlace(): void
    {
        if (count($this->landmines) >= 3) {
            $key = array_key_first($this->landmines);
            $landmine = $this->landmines[$key];

            if (!$landmine->isFlaggedForDespawn() && !$landmine->isClosed()) {
                $landmine->flagForDespawn();
                unset($this->landmines[$key]);
            }
        }
    }

    public function addLandmine(Landmine $landmine): void
    {
        $this->landmines[$landmine->getId()] = $landmine;
    }

    public function removeLandmine(Landmine $landmine): void
    {
        if (!isset($this->landmines[$landmine->getId()])) {
            return;
        }

        unset($this->landmines[$landmine->getId()]);
    }

    public function addBridgeEgg(BridgeEgg $egg, Player $player): void
    {
        $this->bridgeEggs[$player->getXuid()][$egg->getId()] = $egg;
    }

    public function removeBridgeEgg(BridgeEgg $egg, Player $player): void
    {
        if (!isset($this->bridgeEggs[$playerXuid = $player->getXuid()][$eggId = $egg->getId()])) {
            return;
        }

        unset($this->bridgeEggs[$playerXuid][$eggId]);
    }

    public function removePlayerBridgeEggs(Player $player): void
    {
        foreach ($this->bridgeEggs[$player->getXuid()] ?? [] as $egg) {
            $egg->setOwningEntity(null);
        }
    }

    public function getFlagPickupSpeedMultiplier(): float
    {
        $level = $this->getUpgradeLevel(Upgrade::FLAG_PICKUP_SPEED());
        return match ($level) {
            1 => 1.15,
            2 => 1.3,
            default => 1.0,
        };
    }
}