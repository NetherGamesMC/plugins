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

namespace bedwars;

use bedwars\generators\TeamGenerator;
use bedwars\shops\ShopCategory;
use bedwars\shops\Upgrade;
use bedwars\shops\UpgradeableShopItem;
use bedwars\utils\entity\BridgeEgg;
use bedwars\utils\entity\Landmine;
use bedwars\utils\entity\mob\MiniSkeleton;
use bedwars\utils\Items;
use bedwars\utils\StatsData;
use bedwars\utils\TrapManager;
use bedwars\utils\Utils;
use Closure;
use libminigames\Team;
use NetherGames\NGEssentials\entity\custom\EntityNPC;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\VanillaBlocks;
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
use function count;
use function in_array;
use function krsort;
use function str_replace;
use function strtoupper;
use function substr;
use function time;
use function ucwords;

/**
 * @phpstan-type PermanentValue Item[]|bool|int
 * @phpstan-type PermanentValueList array<int|string, PermanentValue>
 */
final class BWTeam extends Team
{
    public const ARMOR = "armor";
    public const SHEARS = "shears";
    public const PICKAXE = "Pickaxes";
    public const AXE = "Axes";

    /** @var array<string, array<int, Item>> */
    private array $playerEnderChestContents = [];
    private bool $bedDefense = false;
    private ?TeamGenerator $generator = null;
    /**
     * A mapping of the available upgrade names to the current tier
     * @var array<string, int>
     */
    private array $upgrades = [];
    /** @var array<string, PermanentValueList> - A mapping of player names to their associated permanent value lists */
    private array $permanent = [];
    private TrapManager $trapManager;

    private ?string $bedDestroyerXuid = null;
    private ?int $bedDestroyTime = null;

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
        $arenaType = $arena->getType();
        $isVersus = $arena->isVersus();

        $teamId = $this->getId();

        $this->generator = $arena->hasTeamGenerator() ? new TeamGenerator(Location::fromObject($arenaConfig->getTeamGenerator($arena, $teamId), $world), $this) : null;
        $this->trapManager = new TrapManager($this);

        if (!($isVersus && in_array($arenaType, [BWArena::TYPE_FIREBALL, BWArena::TYPE_BEDFIGHT]))) {
            $this->spawnShopkeeper(
                $arenaConfig->getTeamShop($arena, $this->getId(), $world),
                TextFormat::AQUA . 'ITEM SHOP' . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . 'RIGHT CLICK',
                static function (Player $player, BWTeam $team, bool $chestUI) use ($arena) {
                    $arena->getShop()->send($player, $team, $chestUI);
                }
            );

            $this->spawnShopkeeper(
                $arenaConfig->getTeamUpgrader($arena, $this->getId(), $world),
                TextFormat::AQUA . strtoupper($arena->getPlugin()->getModeName($arena->getModeId())) . TextFormat::EOL . TextFormat::AQUA . 'UPGRADES' . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . 'RIGHT CLICK',
                static function (Player $player, BWTeam $team, bool $chestUI) use ($arena) {
                    $arena->getUpgrader()->send($player, $team, $chestUI);
                }
            );
        }

        if ($isVersus && in_array($arenaType, [BWArena::TYPE_FIREBALL, BWArena::TYPE_BEDFIGHT])) {
            $this->setUpgradeLevel(Upgrade::ARMOR(), 2);
            foreach ($this->getPlayers() as $player) {
                $this->setPermanent($player, self::ARMOR, [
                    ArmorInventory::SLOT_LEGS => VanillaItems::IRON_LEGGINGS(),
                    ArmorInventory::SLOT_FEET => VanillaItems::IRON_BOOTS()
                ]);
            }
        }
    }

    /**
     * @param Closure(Player, BWTeam, bool): void $callable
     * @return void
     */
    private function spawnShopkeeper(Location $location, string $name, Closure $callable): void
    {
        $arena = $this->getArena();
        $ess = $arena->getPlugin()->getEssentials();
        $gameSettings = $ess->getPlayerData()->getGameSettings();

        $onClick = static function (Player $player) use ($arena, $gameSettings, $callable) {
            if (!$arena->isSpectator($player) && ($team = $arena->getTeamNull($player)) !== null) {
                $chestUI = $gameSettings->getBool($player, GameSettings::BW_CHEST_UI);
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

    public function getArena(): BWArena
    {
        /** @var BWArena $arena */
        $arena = parent::getArena();
        return $arena;
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
        if (($level = $this->getUpgradeLevel(Upgrade::BLAST_PROTECTION())) > 0) {
            $helmet->addEnchantment(new EnchantmentInstance(VanillaEnchantments::BLAST_PROTECTION(), $level));
            $chestplate->addEnchantment(new EnchantmentInstance(VanillaEnchantments::BLAST_PROTECTION(), $level));
            $leggings->addEnchantment(new EnchantmentInstance(VanillaEnchantments::BLAST_PROTECTION(), $level));
            $boots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::BLAST_PROTECTION(), $level));
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
                if ($this->isBedAlive()) {
                    $arena->addKill($damager, $player);
                    $arena->broadcastMessage(
                        str_replace(['{PLAYER}', '{DAMAGER}'], [$this->getPlayerName($player), $damagerTeam->getPlayerName($damager)], $arena->getPlugin()->getRandomKillMessage(1, true)),
                        true
                    );
                } else {
                    $arena->addFinalKill($damager, $player);
                    $arena->broadcastMessage(
                        str_replace(['{PLAYER}', '{DAMAGER}'], [$this->getPlayerName($player), $damagerTeam->getPlayerName($damager)], $arena->getPlugin()->getRandomKillMessage(1, true)) . ' §l§bFINAL KILL!',
                        true
                    );
                }
            } else if (($bedDestroyer = $this->getActiveBedDestroyer()) !== null) {
                $breakerTeam = $arena->getTeam($bedDestroyer);
                $arena->addFinalKill($bedDestroyer, $player);
                $arena->broadcastMessage(
                    str_replace(['{PLAYER}', '{DAMAGER}'], [$this->getPlayerName($player), $breakerTeam->getPlayerName($bedDestroyer)], $arena->getPlugin()->getRandomKillMessage(1, true)) . ' §l§bFINAL KILL!',
                    true
                );
            }

            $arena->getPlugin()->generateDrop($player, null, true);

            foreach ($player->getEnderInventory()->getContents() as $item) {
                if (in_array($item->getTypeId(), [ItemTypeIds::IRON_INGOT, ItemTypeIds::GOLD_INGOT, ItemTypeIds::DIAMOND, ItemTypeIds::EMERALD], true)) {
                    $this->getGenerator()?->dropItem($item, false);
                }
            }

            if (!$this->isBedAlive()) {
                $this->checkElimination();
            }
        }
    }

    public function isBedAlive(): bool
    {
        return $this->bedDestroyTime === null;
    }

    public function onBedDestroy(?Player $destroyer = null): void
    {
        $this->bedDestroyTime = time();
        $this->bedDestroyerXuid = $destroyer?->getXuid();
    }

    public function getActiveBedDestroyer(): ?Player
    {
        if (
            $this->bedDestroyerXuid !== null &&
            $this->bedDestroyTime + 2 * 60 > time() &&
            ($bedDestroyer = $this->getArena()->getPlugin()->getServer()->getPlayerByXuid($this->bedDestroyerXuid)) !== null &&
            $this->getArena()->isInArena($bedDestroyer)
        ) {
            return $bedDestroyer;
        }

        return null;
    }

    public function hasBedDefense(): bool
    {
        return $this->bedDefense;
    }

    public function setBedDefense(bool $bedDefense): void
    {
        $this->bedDefense = $bedDefense;
    }

    public function getGenerator(): ?TeamGenerator
    {
        return $this->generator;
    }

    public function checkElimination(): void
    {
        if (count($this->getAlivePlayers()) === 0) {
            $this->getArena()->broadcastMessage("\n" . TextFormat::BOLD . 'TEAM ELIMINATED > ' . TextFormat::RESET . $this->getColor() . ucwords(str_replace('_', ' ', $this->getName())) . ' Team' . TextFormat::RED . ' has been eliminated!', true);
            $this->getArena()->broadcastMessage("\n", true);
        }

        $this->updateScoreboardEntry();
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

            if ($arena->isVersus()) {
                $wool = VanillaBlocks::WOOL()->setColor($this->getDyeColor())->asItem();
                if ($arena->getType() === BWArena::TYPE_FIREBALL) {
                    $wool->setCount(32)->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Disappearing Wool');
                    $player->getInventory()->addItem(BWItems::FIREBALL()->setCount(16));
                } else {
                    $wool->setCount(64);
                }
                $player->getInventory()->addItem($wool);


                if (in_array($arena->getType(), [BWArena::TYPE_RUSH, BWArena::TYPE_FIREBALL, BWArena::TYPE_BEDFIGHT], true)) {
                    $this->setPermanent($player, self::PICKAXE, 1);
                    $this->setPermanent($player, self::AXE, 1);
                    $this->setPermanent($player, self::SHEARS, 1);
                }
            }

            foreach ($this->getPermanentTools($player, true) as $tool) {
                $player->getInventory()->addItem($tool);
            }
        }

        $player->teleport($arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, $this->getId()));
        $player->setGamemode(GameMode::SURVIVAL);
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if (($arena = $this->getArena())->isWaiting()) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 0, false));
            $inventory = $player->getInventory();
            if ($arena->isVersus()) {
                if ($arena->isCreator($player)) {
                    $inventory->setItem(Items::EXTRA_ITEM_1, Items::getTypeSelectionAnvil());
                } elseif ($arena->isPrivateGame() || $player->hasPermission(Permissions::RANK_VOTER)) {
                    $inventory->setItem(Items::PRIVATE_GAME_SETTINGS, Items::getTypeSelectionAnvil());
                }
            }
        }
    }

    public function reconnectPlayer(Player $player): void
    {
        /** @var NGPlayer $player */

        $arena = $this->getArena();
        $this->addPlayer($player);
        $player->setNameTag($this->getPlayerName($player, true));

        if ($this->isBedAlive()) {
            $player->sendConditionalMessage(TextFormat::YELLOW . 'You respawned because you still have a bed!');
            $arena->broadcastMessage($this->getPlayerName($player) . TextFormat::GRAY . ' reconnected', true);

            if (isset($this->playerEnderChestContents[$player->getName()])) {
                $player->getEnderInventory()->setContents($this->playerEnderChestContents[$player->getName()]);
            }

            /** @var NGPlayer $player */
            $player->setEnergized();
            $this->spawnPlayer($player, false);
        } else {
            $player->sendConditionalMessage(TextFormat::YELLOW . 'Your bed was destroyed so you are a spectator!');
            $arena->addSpectator($player);
            $player->teleport($arena->getWorld()->getSpawnLocation());
        }

        $arena->getScoreboard()->addPlayer($player);
        $this->sendScoreboard($player);
    }

    protected function getScoreboardEntry(): string
    {
        $letter = strtoupper(substr($this->getName(), 0, 1));
        $name = ucwords(str_replace("_", " ", $this->getName()));
        $prefix = TextFormat::BOLD . $this->getColor() . $letter . TextFormat::RESET . ' ' . $name . ':';

        return match (true) {
            ($playerCount = count($this->getAlivePlayers())) === 0 => $prefix . CustomIcon::NO,
            $this->isBedAlive() => $prefix . CustomIcon::YES,
            default => $prefix . ' ' . TextFormat::GREEN . $playerCount
        };
    }

    private function getTeamIndex(): int
    {
        $counter = 0;

        $arena = $this->getArena();
        $teams = $arena->getTeams();
        $versus = $arena->isVersus();
        $teamSize = $arena->getTeamSize();
        krsort($teams);

        foreach ($teams as $team) {
            if ($this === $team) {
                break;
            }
            $counter += $versus ? $teamSize + 1 : 1;
        }

        if ($arena->isTriosOrSquads()) {
            $counter += 7;
        } else {
            $counter += 3;
        }

        return $counter;
    }

    public function updateScoreboardEntry(): void
    {
        $arena = $this->getArena();
        $playerManager = $arena->getPlugin()->getEssentials()->getPlayerManager();
        $scoreboard = $arena->getScoreboard();
        $entry = $this->getScoreboardEntry();
        $index = $this->getTeamIndex();
        [$teamPlayers, $otherPlayers] = $arena->splitTeamPlayers($this);
        $array = [];

        if ($arena->isVersus()) {
            for ($i = 0; $i < $arena->getTeamSize(); $i++) {
                $p = $teamPlayers[$i] ?? null;
                $line = (($p === null || $arena->isSpectator($p)) ? CustomIcon::SKULL . " " . TextFormat::GRAY : TextFormat::GREEN);
                $array[$index++] = $line . ($p === null ? "" : $playerManager->getPlayerName($p));
            }
        }

        $array[$index] = $entry;
        $scoreboard->setLines($otherPlayers, $array);

        $array[$index] .= TextFormat::GRAY . ' YOU';
        $scoreboard->setLines($teamPlayers, $array);
    }

    public function sendScoreboard(?Player $player = null): void
    {
        $arena = $this->getArena();
        $playerManager = $arena->getPlugin()->getEssentials()->getPlayerManager();
        $teams = $arena->getTeams();
        $statsData = $arena->getStatsData();
        $versus = $arena->isVersus();
        krsort($teams);

        $array = [];
        if ($arena->isTriosOrSquads()) {
            $index = 7;

            $array[6] = "";
            if ($player === null) {
                $array[5] = CustomIcon::KILLS . TextFormat::GREEN . 0;
                $array[4] = CustomIcon::CROSSHAIR . TextFormat::GREEN . 0;
                $array[3] = CustomIcon::BED_RED . TextFormat::GREEN . 0;
            } else {
                $array[5] = CustomIcon::KILLS . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_KILLS);
                $array[4] = CustomIcon::CROSSHAIR . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_FINAL_KILLS);
                $array[3] = CustomIcon::BED_RED . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_BEDS_BROKEN);
            }
        } else {
            $index = 3;
        }

        foreach ($teams as $team) {
            if ($versus) {
                $teamPlayers = $team->getPlayers();

                for ($i = 0; $i < $arena->getTeamSize(); $i++) {
                    $p = $teamPlayers[$i] ?? null;
                    $line = (($p === null || $arena->isSpectator($p)) ? CustomIcon::SKULL . " " . TextFormat::GRAY : TextFormat::GREEN);
                    $array[$index++] = $line . ($p === null ? "" : $playerManager->getPlayerName($p));
                }
            }

            $array[$index++] = $team->getScoreboardEntry() . ($team === $this ? TextFormat::GRAY . ' YOU' : "");
        }

        $array[$index] = "";
        $array[$index + 1] = CustomIcon::HOURGLASS;
        $array[$index + 2] = "";
        $array[2] = "";
        $array[1] = CustomIcon::NETHERGAMES . TextFormat::GOLD . "ngmc.co";
        $arena->getScoreboard()->setLines($player === null ? $this->getPlayers() : [$player], $array);
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
        if (!isset($this->landmines[$landmineId = $landmine->getId()])) {
            return;
        }

        unset($this->landmines[$landmineId]);
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
}