<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars;

use libminigames\Minigame;
use libminigames\settings\GameSettings;
use libminigames\Team;
use libminigames\TeamArena;
use libminigames\utils\StatsData as StatsDataAlias;
use libminigames\utils\TypeArena;
use libminigames\utils\TypeArenaTrait;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use skywars\handler\BaseHandler;
use skywars\handler\DuelsHandler;
use skywars\handler\NormalHandler;
use skywars\tasks\CountDownTask;
use skywars\tasks\MatchTimeTask;
use skywars\utils\ChestManager;
use skywars\utils\CircleManager;
use skywars\utils\StatsData;
use function array_keys;
use function array_rand;
use function count;
use function floor;
use function is_array;
use function min;

class SWArena extends TeamArena implements TypeArena
{
    use TypeArenaTrait {
        TypeArenaTrait::checkTypeVotes as checkTypeVotesTrait;
    }

    public const MODE_DUELS_SOLO = 5;
    public const MODE_DUELS_DOUBLES = 6;

    public const TYPE_NORMAL = 0;
    public const TYPE_INSANE = 1;
    public const TYPE_LUCKY_BLOCK = 2;

    /** @var array<string, int> */
    public array $assists = [];

    /** @var bool */
    private bool $canUseCorrupted = false;

    /** @var ChestManager */
    private ChestManager $chestManager;
    /** @var BaseHandler|DuelsHandler|NormalHandler */
    private BaseHandler|NormalHandler|DuelsHandler $handler;

    private ?CircleManager $borderManager = null;

    public function __construct(Skywars $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, new SWArenaSettings());

        $this->type = self::TYPE_NORMAL;

        $this->listener = new SWArenaListener($this);
        $this->statsData = new StatsData($plugin->getModeName($modeId), SWArena::getTypes());
        $this->chestManager = new ChestManager($this);

        if ($modeId === self::MODE_DUELS_SOLO || $modeId === self::MODE_DUELS_DOUBLES) {
            $this->teams = [
                new SWTeam($this, Team::RED),
                new SWTeam($this, Team::DARK_BLUE)
            ];
            $this->handler = new DuelsHandler($this);
        } else {
            for ($x = 0; $x <= 11; $x++) {
                $this->teams[$x] = new SWTeam($this, $x);
            }
            $this->handler = new NormalHandler($this);
        }

        $arenas = $this->getPlugin()->getMaps($this->isDuelsGame(), !$privateGame);
        $maps = $privateGame ? array_keys($arenas) : array_rand($arenas, min(5, count($arenas)));

        if (is_array($maps)) {
            foreach ($maps as $map) {
                $this->maps[] = $arenas[$map];
            }
        } else {
            $this->maps[] = $arenas[$maps];
        }
    }

    /**
     * @return SWArenaSettings
     */
    public function getGameSettings(): GameSettings
    {
        /**
         * @var SWArenaSettings $settings
         */
        $settings = parent::getGameSettings();
        return $settings;
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_NORMAL => "Normal",
            self::TYPE_INSANE => "Insane",
            //self::TYPE_LUCKY_BLOCK => 'LuckyBlock'
        ];
    }

    /**
     * @return Skywars
     */
    public function getPlugin(): Minigame
    {
        /** @var Skywars $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    public function isDuelsGame(): bool
    {
        return $this->getModeId() === self::MODE_DUELS_SOLO || $this->getModeId() === self::MODE_DUELS_DOUBLES;
    }

    public function canUseCorrupted(): bool
    {
        return $this->canUseCorrupted;
    }

    public function setCanUseCorrupted(bool $canUseCorrupted): void
    {
        $this->canUseCorrupted = $canUseCorrupted;
    }

    /**
     * @return ChestManager
     */
    public function getChestManager(): ChestManager
    {
        return $this->chestManager;
    }

    /**
     * @return BaseHandler
     */
    public function getHandler(): BaseHandler
    {
        return $this->handler;
    }

    /**
     * @return SWTeam[]
     */
    public function getTeams(): array
    {
        /** @var SWTeam[] $teams */
        $teams = parent::getTeams();

        return $teams;
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        $modeId = $this->getModeId();
        if (($kills = $this->getStatsData()->getValue($player, StatsDataAlias::KILLS)) !== 0) {
            if (!$this->isDuelsGame()) {
                $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "GAME SUMMARY:");
                $player->sendMessage(CustomIcon::SWORD . $kills . " Kill" . ($kills > 1 ? "s" : ""));
            }

            $data[self::DATA_CREDITS][] = [
                $kills . " Kill" . ($kills > 1 ? "s" : ""),
                $kills * (match ($modeId) {
                    self::MODE_SOLO => 3,
                    self::MODE_DOUBLES => 4,
                    default => 1 // duels earns the same amount
                })
            ];
        }

        if ($this->isDuelsGame()) {
            if ($this->isWinner($player)) {
                $data[self::DATA_CREDITS][] = ['Win', 1];
            }
        } else {
            $guildXP = true;

            if (($assists = ($this->assists[$player->getName()] ?? 0)) !== 0) {
                $data[self::DATA_XP][] = [
                    $assists . ' Assist' . ($assists > 1 ? 's' : ''),
                    ceil($assists / 4)
                ];
            }

            if ($this->isWinner($player)) {
                $data[self::DATA_CREDITS][] = ['Win', match ($modeId) {
                    self::MODE_SOLO => 5,
                    self::MODE_DOUBLES => 7,
                    self::MODE_DUELS_SOLO => 2,
                    self::MODE_DUELS_DOUBLES => 3,
                    default => 1
                }];
                $data[self::DATA_COINS][] = ['Win', 2];
            }
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function getTeamSize(): int
    {
        if ($this->getModeId() === self::MODE_DUELS_DOUBLES) {
            return 2;
        }

        return parent::getTeamSize();
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        parent::resetPlayer($player);

        $this->handler->resetPlayer($player);
    }

    /**
     * @param Player $player
     * @return SWTeam
     */
    public function getTeam(Player $player): Team
    {
        /** @var SWTeam $team */
        $team = parent::getTeam($player);

        return $team;
    }

    public function addKill(Player $player, Player $victim): void
    {
        $this->playKillCosmetics($player);

        if (!$this->isSpectator($player)) {
            $effects = $player->getEffects();
            $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
            $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));
        }

        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsDataAlias::KILLS);
        if ($this->isDuelsGame()) {
            $statsData->addKill($player, $victim, StatsData::DUELS_KILLS);
        } else {
            $statsData->addKill($player, $victim, StatsData::SW_KILLS);
            $statsData->addKill($player, $victim, StatsData::SW_MODE_KILLS);
            $statsData->addKill($player, $victim, StatsData::SW_MODE_TYPE_KILLS);

            $this->getScoreboard()->setLine([$player], 6, CustomIcon::KILLS . TextFormat::GREEN . $statsData->getValue($player, StatsData::SW_KILLS));
        }

        $combatLog = $this->getPlugin()->getEssentials()->getCombatLogger()->getLog($victim);
        foreach ($combatLog->getAssists() as $assist) {
            if (($playerAssist = $this->getPlugin()->getServer()->getPlayerExact($assist)) === null || $playerAssist === $player) {
                continue;
            }
            if ($this->isDuelsGame()) {
                $statsData->addValue($playerAssist, StatsData::DUELS_KILL_ASSISTS);
                $statsData->addValue($playerAssist, StatsData::DUELS_MODE_KILL_ASSISTS);
            } else {
                $statsData->addValue($playerAssist, StatsData::SW_KILL_ASSISTS);
                $statsData->addValue($playerAssist, StatsData::SW_MODE_KILL_ASSISTS);
            }
        }
    }

    public function addAssist(string $playerName): void
    {
        $kills = $this->assists[$playerName] ?? 0;
        $this->assists[$playerName] = ++$kills;
    }

    public function sendStats(): void
    {
        if (!$this->isDuelsGame()) {
            $this->getStatsData()->sendLeaderboard($this, StatsData::SW_KILLS, '§l§aTOP KILLERS');
        }
    }

    public function bootMinigame(): void
    {
        $plugin = $this->getPlugin();
        $plugin->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);

        $world = $this->getWorld();
        $leaderboards = $plugin->getLeaderboards();
        $entityManager = $plugin->getEssentials()->getEntityManager();

        [$title, $text] = $leaderboards->get('sw_*mode*_wins', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(980.5, 70, 991.5, $world, 0.0, 0.0), $title, $text));
        [$title, $text] = $leaderboards->get('sw_*mode*_kills', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(1002.5, 73, 974.5, $world, 0.0, 0.0), $title, $text));
    }

    public function startGame(): void
    {
        $gameSettings = $this->getGameSettings();

        if ($gameSettings->hasBorder()) {
            $this->borderManager = new CircleManager($this->world->getSpawnLocation()->floor(), [
                'state-1' => $gameSettings->getBorderSize() . ";" . $gameSettings->getBorderTime(),
                'state-2' => floor($gameSettings->getBorderSize() / 2) . ";" . $gameSettings->getBorderTime(),
                'state-3' => floor($gameSettings->getBorderSize() / 3) . ";" . $gameSettings->getBorderTime(),
                'state-4' => floor($gameSettings->getBorderSize() / 4) . ";" . $gameSettings->getBorderTime(),
                'final-round' => "25;" . $gameSettings->getBorderTime() . ";600",
            ], function (float $totalSize): void {
                static $firstRun = true;

                if ($firstRun) {
                    $firstRun = false;
                    return;
                }

                $center = $this->world->getSpawnLocation()->floor();
                $area = new AxisAlignedBB(
                    $center->x - $totalSize,
                    World::Y_MIN,
                    $center->z - $totalSize,
                    $center->x + $totalSize,
                    World::Y_MAX,
                    $center->z + $totalSize,
                );

                $this->broadcastMessage(TextFormat::RED . "The border has shrunk to " . TextFormat::GOLD . floor($area->getXLength()) . "x" . floor($area->getZLength()) . TextFormat::RED . "! Get inside the border!");
            });

            $this->getPlugin()->getScheduler()->scheduleRepeatingTask(
                task: new ClosureTask(
                    function () use ($gameSettings): void {
                        foreach ($this->getAlivePlayers() as $alivePlayer) {
                            $this->borderManager->renderParticles($alivePlayer);

                            if (!$this->borderManager->isInsideBorder($alivePlayer->getPosition()->floor())) {
                                $alivePlayer->attack(new EntityDamageEvent($alivePlayer, EntityDamageEvent::CAUSE_CONTACT, $gameSettings->getBorderDamage()));
                                $alivePlayer->sendJukeboxPopup(TextFormat::RED . "You are outside the safezone area. Get back inside the border!");
                            }
                        }
                    }
                ),
                period: 20
            );
        }

        if ($gameSettings->hasPositionSwitch()) {
            $this->getPlugin()->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function (): void {
                $players = $this->getAlivePlayers();

                while (count($players) > 1) {
                    [$player1Index, $player2Index] = array_rand($players, 2);

                    $player1 = $players[$player1Index];
                    $player2 = $players[$player2Index];

                    if (!$player1->isConnected()) {
                        unset($players[$player1Index]);
                        continue;
                    }

                    if (!$player2->isConnected()) {
                        unset($players[$player2Index]);
                        continue;
                    }

                    unset($players[$player1Index], $players[$player2Index]);

                    $player1Pos = $player1->getPosition();
                    $player2Pos = $player2->getPosition();

                    $player1->teleport($player2Pos);
                    $player2->teleport($player1Pos);

                    $player1->sendMessage(TextFormat::GREEN . "You have been teleported to a random player!");
                    $player2->sendMessage(TextFormat::GREEN . "You have been teleported to a random player!");
                }
            }
            ), 20 * 60, 20 * 60);
        }

        $this->chestManager->setup();
        $this->handler->startGame();

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
    }

    public function setupMapFeatures(World $world): void
    {
        $this->handler->setupMapFeatures($world);
    }

    public function getStreaksKey(): ?string
    {
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    public function isSoloGame(): bool
    {
        return parent::isSoloGame() || $this->getModeId() === self::MODE_DUELS_SOLO;
    }

    /**
     * @return SWTeam[]
     */
    public function getAliveTeams(): array
    {
        /** @var SWTeam[] $aliveTeams */
        $aliveTeams = parent::getAliveTeams();

        return $aliveTeams;
    }

    public function getBorderManager(): ?CircleManager
    {
        return $this->borderManager;
    }

    public function checkTypeVotes(): void
    {
        if ($this->isPrivateGame()) {
            $this->type = $this->getGameSettings()->getType() === "NORMAL" ? self::TYPE_NORMAL : self::TYPE_INSANE;
            return;
        }

        $this->checkTypeVotesTrait();
    }
}
