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

use bedwars\generators\Generator;
use bedwars\generators\GeneratorEnum;
use bedwars\generators\ItemGenerator;
use bedwars\generators\TeamGenerator;
use bedwars\shops\Shop;
use bedwars\shops\Upgrader;
use bedwars\tasks\CountDownTask;
use bedwars\tasks\GeneratorTickTask;
use bedwars\tasks\MatchTimeTask;
use bedwars\utils\StatsData;
use bedwars\utils\world\PlayerSwapUtils;
use libminigames\ArenaListener;
use libminigames\settings\GameSettings;
use libminigames\Team;
use libminigames\TeamArena;
use libminigames\utils\BlockCollector;
use libminigames\utils\TypeArena;
use libminigames\utils\TypeArenaTrait;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\block\Bed;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\World;
use RuntimeException;
use function array_diff;
use function array_keys;
use function array_map;
use function array_rand;
use function count;
use function min;
use function random_int;
use function time;

final class BWArena extends TeamArena implements TypeArena
{
    use TypeArenaTrait {
        TypeArenaTrait::checkTypeVotes as private parentCheckTypeVotes;
    }

    public const MODE_VERSUS_ONE = 5;
    public const MODE_VERSUS_TWO = 6;

    public const TYPE_NORMAL = 0;
    public const TYPE_RUSH = 1;
    public const TYPE_FIREBALL = 2;
    public const TYPE_BEDFIGHT = 3;

    /** @var array<ItemGenerator[]> */
    private array $generators = [];
    private ?AxisAlignedBB $worldBorder = null;
    private ?GeneratorTickTask $generatorTask = null;
    private BlockCollector $blockCollector;

    private Shop $shop;
    private Upgrader $upgrader;

    private ?int $spawnY = null;

    private ?PlayerSwapUtils $playerSwapUtils = null;

    public function __construct(Bedwars $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, new BWSettings());

        $this->listener = new BWArenaListener($this);
        $this->blockCollector = new BlockCollector();
        $this->statsData = new StatsData($plugin->getModes()[$modeId]);
        // initialize shop & upgrader
        $this->shop = new Shop();
        $this->upgrader = new Upgrader();

        $this->teams = array_map(fn(int $team) => new BWTeam($this, $team), match ($modeId) {
            self::MODE_TRIOS, self::MODE_SQUADS => [
                Team::RED,
                Team::DARK_BLUE,
                Team::GREEN,
                Team::YELLOW
            ],

            self::MODE_SOLO, self::MODE_DOUBLES => [
                Team::RED,
                Team::DARK_BLUE,
                Team::GREEN,
                Team::YELLOW,
                Team::DARK_AQUA,
                Team::WHITE,
                Team::LIGHT_PURPLE,
                Team::DARK_GRAY
            ],

            default => [
                Team::RED,
                Team::DARK_BLUE,
            ]
        });

        $arenas = $this->getPlugin()->getMaps($modeId, !$privateGame);
        $maps = $privateGame ? array_keys($arenas) : array_rand($arenas, min(5, count($arenas)));

        if (is_array($maps)) {
            foreach ($maps as $map) {
                $this->maps[] = $arenas[$map];
            }
        } else {
            $this->maps[] = $arenas[$maps];
        }

        if (!$privateGame && date('m-d', time()) === '04-01') { // April Fools
            /** @noinspection PhpUndefinedFieldInspection */
            (function (): void {
                $this->noProtection = true;
            })->call($this->getGameSettings());
        }
    }

    public function checkTypeVotes(): void
    {
        if ($this->isVersus()) {
            $this->parentCheckTypeVotes();
        } else {
            $this->type = self::TYPE_NORMAL;
        }
    }

    public function getPlugin(): Bedwars
    {
        /** @var Bedwars $plugin */
        $plugin = parent::getPlugin();
        return $plugin;
    }

    public function getBlockCollector(): BlockCollector
    {
        return $this->blockCollector;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getUpgrader(): Upgrader
    {
        return $this->upgrader;
    }

    public function getPlayerSwapUtils(): ?PlayerSwapUtils
    {
        return $this->playerSwapUtils;
    }

    public function getStreaksKey(): ?string
    {
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    public function getLatestActiveHitter(Player $entity): ?Player
    {
        $plugin = $this->getPlugin();

        if (
            ($hit = $plugin->getEssentials()->getCombatLogger()->getLog($entity)->getLatestHit()) !== null &&
            $hit->getTime() + 15 > time() &&
            ($damager = $plugin->getServer()->getPlayerExact($hit->getDamagerName())) !== null &&
            $this->isInArena($damager)
        ) {
            return $damager;
        }

        return null;
    }

    public function hasPerfectGame(Player $player): bool
    {
        $initialActiveTeamAmount = $this->getInitialActiveTeamCount();

        return match ($this->getModeId()) {
                self::MODE_SOLO, self::MODE_DOUBLES => $initialActiveTeamAmount >= 6,
                self::MODE_TRIOS, self::MODE_SQUADS => $initialActiveTeamAmount >= 3,
                default => false,
            } && (
                $this->getStatsData()->getValue($player, StatsData::BW_FINAL_KILLS) === ($initialActiveTeamAmount - 1) * $this->getTeamSize()
            ) && (
                $this->getStatsData()->getValue($player, StatsData::BW_BEDS_BROKEN) === $initialActiveTeamAmount - 1
            );
    }

    public function addParticipation(Player $player, array $data = [], bool $guildXP = true): void
    {
        $modeId = $this->getModeId();
        $statsData = $this->getStatsData();
        $gameSummary = [];

        if (($bedsBroken = $statsData->getValue($player, StatsData::BW_BEDS_BROKEN)) > 0) {
            $gameSummary[] = CustomIcon::BED_RED . $bedsBroken . ' Bed' . ($bedsBroken > 1 ? 's' : '') . ' Broken';
            $tempBedsBroken = $statsData->getValue($player, StatsData::BW_BEDS_BROKEN, true);

            $data[self::DATA_XP][] = [
                $bedsBroken . ' Bed' . ($bedsBroken > 1 ? 's' : '') . ' Broken',
                $tempBedsBroken * 5
            ];

            $data[self::DATA_CREDITS][] = [
                $bedsBroken . ' Bed' . ($bedsBroken > 1 ? 's' : '') . ' Broken',
                $tempBedsBroken * (match ($modeId) {
                    self::MODE_SOLO => 5,
                    self::MODE_DOUBLES => 7,
                    self::MODE_SQUADS => 9,
                    default => 2
                })
            ];
        }

        if (($kills = $statsData->getValue($player, StatsData::BW_KILLS)) > 0) {
            $gameSummary[] = CustomIcon::SWORD . $kills . ' Kill' . ($kills > 1 ? 's' : '');
        }

        if (($finalKills = $statsData->getValue($player, StatsData::BW_FINAL_KILLS)) > 0) {
            $gameSummary[] = CustomIcon::KILLS . $finalKills . ' Final Kill' . ($finalKills > 1 ? 's' : '');
            $tempFinalKills = $statsData->getValue($player, StatsData::BW_FINAL_KILLS, true);

            $data[self::DATA_XP][] = [
                $finalKills . ' Final Kill' . ($finalKills > 1 ? 's' : ''),
                $tempFinalKills * 3
            ];

            $data[self::DATA_CREDITS][] = [
                $finalKills . ' Final Kill' . ($finalKills > 1 ? 's' : ''),
                $tempFinalKills * (match ($modeId) {
                    self::MODE_SOLO => 2,
                    self::MODE_DOUBLES, self::MODE_SQUADS => 3,
                    default => 1
                })
            ];
        }

        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                'Win',
                match ($this->getModeId()) {
                    self::MODE_SOLO => 7,
                    self::MODE_DOUBLES => 9,
                    self::MODE_SQUADS => 11,
                    default => 3
                }
            ];

            if ($this->hasPerfectGame($player)) {
                $data[self::DATA_CREDITS][] = [
                    'Perfect Win',
                    match ($this->getModeId()) {
                        self::MODE_SOLO => 3,
                        self::MODE_DOUBLES => 5,
                        self::MODE_SQUADS => 8,
                        default => 0
                    }
                ];
            }
        }

        if (!empty($gameSummary)) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . 'GAME SUMMARY:');

            foreach ($gameSummary as $summary) {
                $player->sendMessage($summary);
            }
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function getTeamSize(): int
    {
        return match ($this->getModeId()) {
            self::MODE_VERSUS_TWO => 2,
            default => parent::getTeamSize()
        };
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        parent::resetPlayer($player);

        if ($this->isRunning() || $this->isFinishing()) {
            $player->setHealthTag(false);

            if ($this->isSpectator($player)) {
                $player->getEnderInventory()->clearAll();
            }
        }
    }

    public function addFinalKill(Player $player, Player $victim): void
    {
        $this->addKill($player, $victim);

        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::KILLS);
        $statsData->addKill($player, $victim, StatsData::BW_FINAL_KILLS);
        $statsData->addKill($player, $victim, StatsData::BW_MODE_FINAL_KILLS);

        if ($this->isTriosOrSquads()) {
            $this->getScoreboard()->setLine([$player], 4, CustomIcon::CROSSHAIR . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_FINAL_KILLS));
        }
    }

    public function isVersus(): bool
    {
        return $this->getModeId() === self::MODE_VERSUS_ONE || $this->getModeId() === self::MODE_VERSUS_TWO;
    }

    public function isTriosOrSquads(): bool
    {
        return $this->getModeId() === self::MODE_TRIOS || $this->getModeId() === self::MODE_SQUADS;
    }

    public function addKill(Player $player, Player $victim): void
    {
        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::BW_KILLS);
        $statsData->addKill($player, $victim, StatsData::BW_MODE_KILLS);

        if ($this->isTriosOrSquads()) {
            $this->getScoreboard()->setLine([$player], 5, CustomIcon::KILLS . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_KILLS));
        }

        $this->playKillCosmetics($player);

        $combatLog = $this->getPlugin()->getEssentials()->getCombatLogger()->getLog($victim);
        foreach ($combatLog->getAssists() as $assist) {
            if (($playerAssist = $this->getPlugin()->getServer()->getPlayerExact($assist)) === null || $playerAssist === $player) {
                continue;
            }
            $statsData->addValue($playerAssist, StatsData::BW_KILL_ASSISTS);
            $statsData->addValue($playerAssist, StatsData::BW_MODE_KILL_ASSISTS);
        }
    }

    public function addBedBroken(Player $player): void
    {
        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsData::BW_BEDS_BROKEN);
        $statsData->addValue($player, StatsData::BW_MODE_BEDS_BROKEN);

        if ($this->isTriosOrSquads()) {
            $this->getScoreboard()->setLine([$player], 3, CustomIcon::BED_RED . TextFormat::GREEN . $statsData->getValue($player, StatsData::BW_BEDS_BROKEN));
        }
    }

    public function bootMinigame(): void
    {
        $plugin = $this->getPlugin();
        $plugin->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);

        $world = $this->getWorld();
        $leaderboards = $plugin->getLeaderboards();
        $entityManager = $plugin->getEssentials()->getEntityManager();

        [$title, $text] = $leaderboards->get('bw_*mode*_wins', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(0, 65, -17, $world, 0.0, 0.0), $title, $text));
    }

    public function setupMapFeatures(World $world): void
    {
        $gameSettings = $this->getGameSettings();
        if ($this->isVersus() && in_array($this->getType(), [self::TYPE_FIREBALL, self::TYPE_BEDFIGHT])) {
            $gameSettings->setEnabledGenerators(iron: false, gold: false, emerald: false, diamond: false);
        } else {
            if ($gameSettings->hasFreeItems()) {
                $gameSettings->setEnabledGenerators(iron: false, gold: false, emerald: false);
            }
            if ($gameSettings->hasMaxedUpgrades()) {
                $gameSettings->setEnabledGenerators(diamond: false);
            }
        }

        $gens = $gameSettings->getEnabledGenerators();
        $arenaConfig = $this->getPlugin()->getArenaConfig();
        if (in_array(GeneratorEnum::EMERALD, $gens)) {
            foreach ($arenaConfig->getGenerators($this, 'emerald') as $id => $value) {
                $this->addGenerator(GeneratorEnum::EMERALD, new ItemGenerator(Location::fromObject($arenaConfig->getGenerator($this, 'emerald', $id), $world), VanillaItems::EMERALD()));
            }
        }

        if (in_array(GeneratorEnum::DIAMOND, $gens)) {
            foreach ($arenaConfig->getGenerators($this, 'diamond') as $id => $value) {
                $this->addGenerator(GeneratorEnum::DIAMOND, new ItemGenerator(Location::fromObject($arenaConfig->getGenerator($this, 'diamond', $id), $world), VanillaItems::DIAMOND()));
            }
        }

        foreach ($this->getTeams() as $team) {
            $this->spawnY ??= $arenaConfig->getTeamSpawn($this, $team->getId())->getFloorY();
            $team->setupTeam($world);
        }
    }

    public function addGenerator(GeneratorEnum $type, ItemGenerator $generator): void
    {
        $this->generators[$type->value][] = $generator;
    }

    /**
     * @return BWTeam[]
     */
    public function getAliveTeams(): array
    {
        /** @var BWTeam[] $aliveTeams */
        $aliveTeams = parent::getAliveTeams();

        return $aliveTeams;
    }

    public function getTeamNull(Player $player): ?BWTeam
    {
        /** @var BWTeam|null $team */
        $team = parent::getTeamNull($player);
        return $team;
    }

    public function getSpawnY(): int
    {
        return $this->spawnY ?? throw new RuntimeException("Height limit not set");
    }

    /**
     * @return BWTeam[]
     */
    public function getTeams(): array
    {
        /** @var BWTeam[] $teams */
        $teams = parent::getTeams();

        return $teams;
    }

    /**
     * @return ItemGenerator[]
     */
    public function getDiamondGenerator(): array
    {
        return $this->generators[GeneratorEnum::DIAMOND->value] ?? [];
    }

    /**
     * @return ItemGenerator[]
     */
    public function getEmeraldGenerator(): array
    {
        return $this->generators[GeneratorEnum::EMERALD->value] ?? [];
    }

    public function sendStats(): void
    {
        $this->getStatsData()->sendLeaderboard($this, StatsData::BW_KILLS, '§l§aTOP KILLERS');
    }

    /**
     * Splits all players from one team from all players in the arena
     * @return list{0: Player[], 1: Player[]}
     */
    public function splitTeamPlayers(BWTeam $team): array
    {
        return [$teamPlayers = $team->getPlayers(), array_diff($this->getPlayers(), $teamPlayers)];
    }

    public function startGame(): void
    {
        $arenaConfig = $this->getPlugin()->getArenaConfig();
        $gameSettings = $this->getGameSettings();

        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center('Bedwars'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Protect your bed and destroy the enemy beds.'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Upgrade yourself and your team by collecting'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Iron, Gold, Emerald and Diamond from the generators'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'to access powerful upgrades.'), true);
        if (($credits = $arenaConfig->getCredits($this)) !== null) {
            $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . $this->getMapDisplayName() . ", by " . $credits), true);
        }
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        if (!$this->isPrivateGame()) {
            if ($this->isSoloGame()) {
                $this->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Teaming is not allowed on Solo mode!', true);
            } else {
                $this->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Cross-teaming with other teams or attacking other team members is not allowed in this game. You will be banned if you attempt or threaten to do so.', true);
            }
        }
        if (!$this->isSoloGame() && random_int(0, 20) === 0) {
            $this->broadcastMessage(TextFormat::GREEN . 'Did you know? ' . TextFormat::GOLD . 'You can shout/talk to all players in your match by using an exclamation mark (' . TextFormat::AQUA . '!' . TextFormat::GOLD . ') in front of your message.', true);
        }

        if ($gameSettings->hasPositionSwap()) {
            [$min, $max] = $gameSettings->getPositionSwapDelays();
            $this->playerSwapUtils = new PlayerSwapUtils($min, $max);
        }

        foreach ($this->getTeams() as $team) {
            if (count($team->getAlivePlayers()) === 0) {
                $team->onBedDestroy();
            } else {
                foreach ($team->getPlayers() as $player) {
                    $player->getEffects()->clear();

                    /** @var NGPlayer $player */
                    $player->setEnergized();
                    $team->spawnPlayer($player, false);

                    $this->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
                }

                $team->sendScoreboard();
            }
        }

        $this->worldBorder = $this->getPlugin()->getArenaConfig()->getBorderLimitConfig($this->getMapName());
        if ($this->worldBorder === null) {
            $this->getPlugin()->getServer()->broadcastMessage(
                TextFormat::RED . "Not enough spawns set to create a world border for arena " . $this->getMapName()
            );
        }

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);

        if (count($gameSettings->getEnabledGenerators()) > 0) {
            $this->getPlugin()->getScheduler()->scheduleRepeatingTask(($this->generatorTask = new GeneratorTickTask($this)), 2);
        }
    }

    public function getTeamByBed(Bed $bed): ?BWTeam
    {
        $bedTile = $this->getWorld()->getTile($bed->getPosition());
        if ($bedTile instanceof \pocketmine\block\tile\Bed) {
            foreach ($this->getTeams() as $team) {
                if ($bedTile->getColor() === $team->getDyeColor()) {
                    return $team;
                }
            }
        }

        return null;
    }

    /**
     * @param Player $player
     * @return BWTeam
     */
    public function getTeam(Player $player): Team
    {
        /** @var BWTeam $team */
        $team = parent::getTeam($player);

        return $team;
    }

    public function getSkin(): Skin
    {
        return new Skin('Standard_Custom', base64_decode('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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvohs/76IbP+yemL/snpi/7J6Yv+yemL/snpi/7J6Yv+9i3L/vYty/7V7Z/+1e2f/tXtn/7V7Z/+1e2f/tXtn/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAC9i3L/vYty/72Lcv+9i3L/vYty/72Lcv+9i3L/tXtn/76IbP+yemL/snpi/7J6Yv+yemL/vohs/7J6Yv+yemL/vYtx/72Lcf+9i3H/vYtx/72Lcf+9i3H/vYtx/7V7Z/+9i3H/vYtx/72Lcf+9i3H/vYtx/72Lcf+9i3H/snpi/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvYty/7V7Z/+9i3L/vYty/72Lcv+9i3L/tXtn/7V7Z/++iGz/snpi/7J6Yv+yemL/snpi/76IbP+yemL/snpi/72Lcf+1e2f/vYtx/72Lcf+9i3H/vYtx/7V7Z/+1e2f/vYtx/7J6Yv+9i3H/vYtx/72Lcf+yemL/snpi/7J6Yv8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAL2Lcv+1e2f/vYty/72Lcv+1e2f/tXtn/72Lcv+1e2f/vohs/7J6Yv+yemL/snpi/7J6Yv+yemL/snpi/7J6Yv+9i3H/tXtn/72Lcf+9i3H/tXtn/7V7Z/+9i3H/tXtn/72Lcf+yemL/vYtx/72Lcf+9i3H/snpi/7J6Yv+yemL/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAC9i3L/vYty/72Lcv+9i3L/tXtn/7V7Z/+9i3L/tXtn/7V7Z/8+Jh7/PiYe/z4mHv8+Jh7/PiYe/z4mHv++iGz/vYty/72Lcf+9i3H/vYtx/7V7Z/+1e2f/vYtx/7V7Z/+9i3H/vYtx/72Lcf+9i3H/snpi/7J6Yv+9i3H/snpi/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAvYty/72Lcv+1e2f/tXtn/7V7Z/+9i3L/tXtn/7V7Z/++iGz//////024NP+0emb/tHpm/024NP//////vohs/72Lcv+9i3L/tXtn/7V7Z/+1e2f/vYtx/7V7Z/+1e2f/vYtx/72Lcf+9i3H/vYtx/7J6Yv+yemL/vYtx/7J6Yv8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAL2Lcv+ze2L/vYty/72Lcv+1e2f/vYty/7V7Z/+1e2f/kF5D/7J6Yv+3gnL/nWhM/51oTP++iGz/snpi/5BeQ/+9i3L/s3ti/72Lcv+9i3L/tXtn/72Lcv+1e2f/tXtn/72Lcf+yemL/snpi/7J6Yv+yemL/snpi/7J6Yv+yemL/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACdaU3/nWlN/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAC1e2f/tXtn/7V7Z/+1e2f/vYty/7V7Z/+1e2f/vYty/5BeQ/+yemL/cEQz/51oTP+daEz/cEQz/7J6Yv+QXkP/vYty/7V7Z/+1e2f/vYty/7V7Z/+1e2f/tXtn/7V7Z/+9i3H/vYtx/7J6Yv+yemL/snpi/7J6Yv+yemL/snpi/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAnWlN/51pTf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAtXtn/7V7Z/+1e2f/tXtn/7V7Z/+1e2f/vYty/72Lcv+QXkP/snpi/7J6Yv+daEz/nWhM/7J6Yv+yemL/kF5D/72Lcv+9i3L/tXtn/7V7Z/+1e2f/tXtn/7V7Z/+1e2f/tXtn/7V7Z/+yemL/snpi/7J6Yv+1e2f/tXtn/7V7Z/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJ1pTf+daU3/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8KiP/PCoj/zwqI/88KiP/iIV8/4iFfP+IhXz/iIV8/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAfVxU/72Lcv+9i3L/vYty/7V7Z/+1e2f/vYty/31cVP84JyH/OCch/zgnIf84JyH/OCch/zgnIf84JyH/OCch/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAblJL/31cVP99XFT/blJL/72Lcf+9i3H/vYtx/7V7Z/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPCoj/zwqI/88KiP/PCoj/4iFfP+IhXz/iIV8/4iFfP8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH1cVP+9i3L/vYty/72Lcv+1e2f/vYty/7V7Z/99XFT/OCch/zgnIf84JyH/OCch/zgnIf84JyH/OCch/zgnIf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH1cVP99XFT/fVxU/31cVP+9i3H/tXtn/72Lcf+9i3H/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADwqI/88KiP/PCoj/zwqI/+IhXz/iIV8/4iFfP+IhXz/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB9XFT/vYty/7V7Z/+1e2f/tXtn/72Lcv+1e2f/fVxU/zgnIf84JyH/OCch/zgnIf84JyH/OCch/zgnIf84JyH/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB9XFT/fVxU/31cVP9uUkv/tXtn/72Lcf+1e2f/vYtx/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8KiP/PCoj/zwqI/88KiP/iIV8/4iFfP+IhXz/iIV8/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAfVxU/7V7Z/+1e2f/tXtn/7V7Z/+1e2f/tXtn/31cVP84JyH/OCch/zgnIf84JyH/OCch/zgnIf84JyH/OCch/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAblJL/31cVP99XFT/blJL/72Lcf+9i3H/tXtn/72Lcf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABEMi7/RDIu/0QyLv9EMi7/cVRN/3FUTf9xVE3/RDIu/0QyLv9EMi7/RDIu/0QyLv9xVE3/blJL/2hMRv9xVE3/WkI9/1pCPf9EMi7/RDIu/2hMRv9TOjH/vYty/7V7Z/+9i3L/tXtn/1M6Mf9qT0j/WkI9/1pCPf9aQj3/WkI9/2pPSP9uUkv/blJL/25SS/9xVE3/cVRN/25SS/9uUkv/WkI9/1pCPf9aQj3/WkI9/3FUTf9xVE3/cVRN/25SS/9aQj3/WkI9/1pCPf9aQj3/cVRN/3FUTf9xVE3/blJL/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAARDIu/0QyLv9MODP/TDgz/2ZKRf9mSkX/cVRN/0QyLv9EMi7/RDIu/0w4M/9MODP/cVRN/2hMRv9oTEb/cVRN/0w4M/9MODP/WkI9/1pCPf9xVE3/cVRN/1M6Mf+9i3L/tXtn/1M6Mf9xVE3/ZkpF/1pCPf9MODP/TDgz/0w4M/9uUkv/cVRN/3FUTf9xVE3/cVRN/3FUTf9uUkv/blJL/0w4M/9MODP/TDgz/1pCPf9uUkv/aExG/3FUTf9uUkv/WkI9/0w4M/9MODP/WkI9/25SS/9oTEb/cVRN/25SS/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEQyLv9EMi7/TDgz/0w4M/9xVE3/cVRN/3FUTf9EMi7/RDIu/0QyLv9MODP/TDgz/3FUTf9uUkv/aExG/25SS/9MODP/TDgz/1pCPf9aQj3/cVRN/3FUTf9xVE3/Uzox/1E5MP9uUkv/cVRN/2ZKRf9MODP/TDgz/0w4M/9MODP/cVRN/3FUTf9xVE3/cVRN/3FUTf9xVE3/cVRN/25SS/9aQj3/TDgz/0w4M/9aQj3/blJL/2hMRv9xVE3/ZkpF/1pCPf9MODP/TDgz/1pCPf9uUkv/aExG/3FUTf9xVE3/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABMODP/TDgz/1pCPf9aQj3/cVRN/3FUTf9xVE3/RDIu/0w4M/9MODP/WkI9/1pCPf9xVE3/cVRN/2hMRv9uUkv/TDgz/0w4M/9EMi7/RDIu/3FUTf9mSkX/cVRN/0QyLv9MODP/ZkpF/3FUTf9mSkX/RDIu/0w4M/9MODP/TDgz/3FUTf9xVE3/aExG/3FUTf9uUkv/aExG/3FUTf9uUkv/WkI9/0w4M/9MODP/WkI9/25SS/9oTEb/blJL/2ZKRf9aQj3/TDgz/0w4M/9aQj3/blJL/2hMRv9uUkv/cVRN/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAATDgz/0w4M/9aQj3/WkI9/3FUTf9xVE3/cVRN/1lBPP9MODP/TDgz/1pCPf9aQj3/cVRN/3FUTf9xVE3/cVRN/0QyLv9EMi7/RDIu/0QyLv9xVE3/cVRN/3FUTf9EMi7/TDgz/35dVv9mSkX/ZkpF/0QyLv9EMi7/RDIu/0QyLv9xVE3/blJL/2hMRv9xVE3/blJL/2hMRv9xVE3/cVRN/0w4M/9MODP/TDgz/1pCPf9xVE3/aExG/2hMRv9xVE3/WkI9/0w4M/9MODP/TDgz/3FUTf9oTEb/aExG/3FUTf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEw4M/9aQj3/WkI9/1pCPf9xVE3/cVRN/1lBPP9ZQTz/TDgz/1pCPf9aQj3/WkI9/2hMRv9oTEb/aExG/3FUTf9EMi7/RDIu/0w4M/9MODP/ZkpF/3FUTf9xVE3/RDIu/0w4M/9xVE3/ZkpF/3FUTf9MODP/RDIu/0QyLv9EMi7/cVRN/2hMRv9oTEb/cVRN/25SS/9oTEb/blJL/3FUTf9MODP/TDgz/0w4M/9aQj3/cVRN/2hMRv9oTEb/ZkpF/0w4M/9aQj3/TDgz/0w4M/9xVE3/aExG/2hMRv9xVE3/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8KiP/NSUf/zwqI/88KiP/WD40/1g+NP9YPjT/WD40/zwqI/81JR//PCoj/zwqI/9YPjT/WD40/1g+NP9YPjT/RDIu/0QyLv9MODP/TDgz/2ZKRf9mSkX/cVRN/0QyLv8/Lyr/fl1W/2ZKRf9xVE3/TDgz/0QyLv9EMi7/TDgz/3FUTf9uUkv/aExG/25SS/9xVE3/aExG/2hMRv9xVE3/WkI9/0w4M/9MODP/WkI9/3FUTf9xVE3/cVRN/3FUTf9MODP/WkI9/0w4M/9MODP/cVRN/3FUTf9xVE3/cVRN/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPCoj/zwqI/88KiP/PCoj/1g+NP9YPjT/WD40/1g+NP88KiP/NSUf/zwqI/88KiP/WD40/1g+NP9YPjT/WD40/0w4M/9MODP/WkI9/1pCPf9xVE3/ZkpF/3FUTf8/Lyr/Py8q/3FUTf9mSkX/cVRN/1pCPf9MODP/TDgz/0w4M/9xVE3/cVRN/2hMRv9uUkv/cVRN/2hMRv9oTEb/cVRN/1pCPf9aQj3/WkI9/1pCPf9oTEb/aExG/3FUTf9uUkv/WkI9/1pCPf9aQj3/WkI9/2hMRv9oTEb/cVRN/25SS/8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADwqI/88KiP/PCoj/zwqI/9YPjT/WD40/1g+NP9YPjT/PCoj/zwqI/88KiP/PCoj/1g+NP9YPjT/WD40/1g+NP9MODP/TDgz/1pCPf9aQj3/cVRN/2ZKRf9xVE3/PzAs/0QyLv9xVE3/ZkpF/3FUTf9aQj3/TDgz/0w4M/9MODP/cVRN/3FUTf9xVE3/cVRN/3FUTf9xVE3/cVRN/3FUTf9rUUn/XUhB/11IQf9dSEH/cVRN/3FUTf9oTEb/cVRN/0c4Mv9HODL/Rzgy/0c4Mv9rUUn/a1FJ/2tRSf9rUUn/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/Pz//Pz8//zwqI/88KiP/WD40/1g+NP9YPjT/WD40/zwqI/88KiP/Pz8//z8/P/9ra2v/a2tr/2tra/9ra2v/TDgz/1pCPf9aQj3/WkI9/3FUTf9mSkX/cVRN/z8wLP8/MCz/cVRN/3FUTf9xVE3/WkI9/0w4M/9MODP/TDgz/2hMRv9oTEb/aExG/3FUTf9oTEb/aExG/3FUTf9uUkv/XUhB/2pMRf9qTEX/akxF/3FUTf9oTEb/aExG/3FUTf9HODL/Rzgy/0c4Mv9HODL/a1FJ/11IQf9dSEH/XUhB/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPz8//z8/P/8/Pz//Pz8//2tra/9ra2v/a2tr/2tra/8/Pz//Pz8//z8/P/8/Pz//a2tr/2tra/9ra2v/a2tr/0QyLv9MODP/RDIu/0QyLv9xVE3/cVRN/3FUTf9EMi7/PzAs/3FUTf9xVE3/cVRN/1pCPf9EMi7/RDIu/0QyLv9xVE3/aExG/2hMRv9uUkv/aExG/2hMRv9oTEb/cVRN/z4uKf8+Lin/Pi4p/z4uKf8+Lin/Pi4p/z4uKf8+Lin/Pi4p/z4uKf8+Lin/Pi4p/z4uKf8+Lin/Pi4p/z4uKf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD8/P/8/Pz//Pz8//z8/P/9ra2v/a2tr/2tra/9ra2v/Pz8//z8/P/8/Pz//Pz8//2tra/9ra2v/a2tr/2tra/9EMi7/TDgz/0w4M/9MODP/cVRN/3FUTf9xVE3/RDIu/z8wLP9xVE3/cVRN/3FUTf9MODP/RDIu/0QyLv9MODP/cVRN/3FUTf9xVE3/aExG/2hMRv9xVE3/cVRN/3FUTf+9i3H/vYtx/72Lcf+9i3H/vYtx/72Lcf+9i3H/vYtx/7V7Z/+1e2f/vYtx/72Lcf+9i3H/vYtx/72Lcf+9i3H/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA8KiP/PCoj/zwqI/88KiP/iIV8/4iFfP+IhXz/iIV8/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAblJL/31cVP99XFT/blJL/7V7Z/+9i3H/vYtx/72Lcf8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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'), '', 'geometry.humanoid.custom');
    }

    /**
     * @return AxisAlignedBB|null
     */
    public function getWorldBorder(): ?AxisAlignedBB
    {
        return $this->worldBorder;
    }

    public function finishGame(): void
    {
        if ($this->generatorTask instanceof GeneratorTickTask && $this->generatorTask->getHandler() !== null) {
            $this->generatorTask->getHandler()->cancel();
        }
    }

    /**
     * @return Generator[]
     */
    public function getGenerators(): array
    {
        return array_merge($this->getGlobalGenerators(), $this->getTeamGenerators());
    }

    /**
     * @return ItemGenerator[]
     */
    public function getGlobalGenerators(): array
    {
        return array_merge($this->getEmeraldGenerator(), $this->getDiamondGenerator());
    }

    /**
     * @return TeamGenerator[]
     */
    public function getTeamGenerators(): array
    {
        if (!$this->hasTeamGenerator()) {
            return [];
        }

        $array = [];

        foreach ($this->getTeams() as $team) {
            if (($generator = $team->getGenerator()) !== null) {
                $array[] = $generator;
            }
        }

        return $array;
    }

    /**
     * @return BWSettings
     */
    public function getGameSettings(): GameSettings
    {
        /** @var BWSettings $gameSettings */
        $gameSettings = parent::getGameSettings();
        return $gameSettings;
    }

    public function isSoloGame(): bool
    {
        return $this->getModeId() === self::MODE_VERSUS_ONE || parent::isSoloGame();
    }

    /**
     * @return array<int, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_NORMAL => "Normal",
            self::TYPE_RUSH => "Rush",
            self::TYPE_FIREBALL => "Fireball Fight",
            self::TYPE_BEDFIGHT => "Bed Fight"
        ];
    }

    /**
     * @return BWArenaListener
     */
    public function getListener(): ArenaListener
    {
        /** @var BWArenaListener $listener */
        $listener = parent::getListener();
        return $listener;
    }

    /**
     * Returns true if teams automatically spawn with a bed defense, false otherwise.
     * @return bool
     */
    public function hasBedDefense(): bool
    {
        if ($this->isVersus()) {
            return match ($this->getType()) {
                self::TYPE_NORMAL => false,
                default => true
            };
        }

        return $this->isPrivateGame() && $this->getGameSettings()->hasAutoBedDefense();
    }

    public function hasTeamGenerator(): bool
    {
        if ($this->isPrivateGame() && $this->getGameSettings()->hasFreeItems()) {
            return false;
        }

        if (!$this->isVersus()) {
            return true;
        }

        return match ($this->getType()) {
            self::TYPE_FIREBALL,
            self::TYPE_BEDFIGHT => false,
            default => true
        };
    }

    public function getGXP(): int
    {
        return $this->isVersus() ? 3 : 10;
    }
}
