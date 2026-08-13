<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use bridges\tasks\ArenaTickTask;
use bridges\tasks\MatchTimeTask;
use bridges\utils\Items;
use bridges\utils\StatsData;
use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use libminigames\ArenaListener;
use libminigames\Minigame;
use libminigames\settings\GameSettings;
use libminigames\tasks\CountDownTask;
use libminigames\Team;
use libminigames\TeamArena;
use libminigames\utils\TypeArena;
use libminigames\utils\TypeArenaTrait;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\types\game\cage\CagesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\utils\Cage;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\StainedHardenedClay;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\World;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_rand;
use function count;
use function max;
use function min;
use function substr;
use function ucwords;

class BridgeArena extends TeamArena implements TypeArena
{
    use TypeArenaTrait;

    public const TYPE_NORMAL = 0;
    public const TYPE_RUSH = 1;

    public const PHASE_RUN = 0;
    public const PHASE_RESTART = 1;
    public const PHASE_FINISH = 2;

    /** @var int */
    public int $time = 6;
    /** @var int */
    public int $phase = self::PHASE_RESTART;
    /** @var int */
    private int $buildHeight = 0;
    /** @var int */
    public int $goalLimit;

    /** @var Cage[] */
    private array $cages;

    private bool $ranOutOfTime = false;

    public function __construct(TheBridge $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, new BridgeSettings());

        $this->listener = new BridgeArenaListener($this);
        $this->statsData = new StatsData($plugin->getModeName($modeId));

        $this->teams = [
            new BridgeTeam($this, BridgeTeam::RED),
            new BridgeTeam($this, BridgeTeam::DARK_BLUE)
        ];

        $arenas = $this->getPlugin()->getMaps(!$privateGame);
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
     * @return TheBridge
     */
    public function getPlugin(): Minigame
    {
        /** @var TheBridge $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_NORMAL => "Normal (5 Points)",
            self::TYPE_RUSH => "Rush (3 Points)",
        ];
    }

    /**
     * @return BridgeArenaListener
     */
    public function getListener(): ArenaListener
    {
        /** @var BridgeArenaListener $listener */
        $listener = parent::getListener();

        return $listener;
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        parent::resetPlayer($player);

        if ($this->isRunning() || $this->isFinishing()) {
            $player->setHealthTag(false);

            $player->getXpManager()->setCurrentTotalXp(0);
        }
    }

    public function getTeamWithHighestScore(): ?BridgeTeam
    {
        [$redTeam, $blueTeam] = $this->getTeams();

        if (count($blueTeam->getAlivePlayers()) === 0 && count($redTeam->getAlivePlayers()) > 0) {
            return $redTeam;
        }

        if (count($redTeam->getAlivePlayers()) === 0 && count($blueTeam->getAlivePlayers()) > 0) {
            return $blueTeam;
        }

        if ($redTeam->getScore() > $blueTeam->getScore()) {
            return $redTeam;
        }

        if ($redTeam->getScore() < $blueTeam->getScore()) {
            return $blueTeam;
        }

        return null;
    }

    /**
     * @return BridgeTeam[]
     */
    public function getTeams(): array
    {
        /** @var BridgeTeam[] $teams */
        $teams = parent::getTeams();

        return $teams;
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if ($this->isWaiting()) {
            if ($this->isSoloGame()) {
                $player->getInventory()->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getPreferencesSelector());
            } else {
                $player->getInventory()->setItem(Items::EXTRA_ITEM_1, Items::getPreferencesSelector());
            }
            $player->getInventory()->setItem(Items::EXTRA_ITEM_3, Items::getTypeSelectionAnvil());
        }
    }

    public function bootMinigame(): void
    {
        $plugin = $this->getPlugin();
        $plugin->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);

        $world = $this->getWorld();
        $leaderboards = $plugin->getLeaderboards();
        $entityManager = $plugin->getEssentials()->getEntityManager();

        [$title, $text] = $leaderboards->get('tb_*mode*_wins', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(3.5, 61, 3.5, $world, 0.0, 0.0), $title, $text));
        [$title, $text] = $leaderboards->get('tb_*mode*_kills', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(-2.5, 61, 3.5, $world, 0.0, 0.0), $title, $text));
        [$title, $text] = $leaderboards->get('tb_*mode*_goals', $this->getModeId());
        $entityManager->addEntity(new FloatingText(new Location(-2.5, 61, -2.5, $world, 0.0, 0.0), $title, $text));
    }

    public function setupMapFeatures(World $world): void
    {
        $pointY = World::Y_MAX;

        foreach ($this->getTeams() as $team) {
            $team->setupTeam($world);

            if ($this->buildHeight === 0) {
                $this->buildHeight = (int)(($team->getPoint()->getY() + $team->getSpawnPosition()->getY()) / 2) + 7;
                $pointY = $team->getPoint()->getY();
            }
        }

        $hasNoBridge = $this->getGameSettings()->hasNoBridge();
        $hasSumoMode = $this->getGameSettings()->hasSumoMode();
        
        if ($hasNoBridge && $hasSumoMode) {
            if ((bool) random_int(0, 1)) {
                $hasSumoMode = false;
            } else {
                $hasNoBridge = false;
            }
            $this->broadcastMessage(TextFormat::YELLOW . "Since you could not decide whether you want a bridge or no bridge, a coin was flipped to decide which option to pick!");
        }

        if (!$hasNoBridge && !$hasSumoMode) {
            $this->spawnCages($world);
            return;
        }

        $selection = new Selection();
        $worldSpawn = $world->getSpawnLocation()->floor();

        $bridgeBlocks = [];
        $bridgeY = $pointY;
        
        foreach (Facing::HORIZONTAL as $side) {
            for ($i = 0; $i <= 20; $i++) {
                $sidePos = $worldSpawn->getSide($side, $i);
                $world->loadChunk($sidePos->getX() >> 4, $sidePos->getZ() >> 4);
                
                $blockDetected = false;
                for ($y = max(0, $pointY - 12); $y <= $this->buildHeight; $y++) {
                    $block = $world->getBlockAt($sidePos->getX(), $y, $sidePos->getZ());
                    
                    if ($block->getTypeId() === BlockTypeIds::STAINED_CLAY) {
                        $bridgeBlocks[] = ['position' => $block->getPosition(), 'block' => $block, 'side' => $side, 'offset' => $i];
                        $bridgeY = max($bridgeY, $y);
                        $blockDetected = true;
                    }
                }
                
                if (!$blockDetected) {
                    break;
                }
            }
        }

        if ($hasNoBridge) {
            $airBlock = VanillaBlocks::AIR();
            foreach ($bridgeBlocks as $bridgeBlock) {
                $selection->addBlock($bridgeBlock['position'], $airBlock);
            }
        } else {
            foreach (Facing::HORIZONTAL as $side) {
                $perpendicular = [Facing::rotateY($side, false), Facing::rotateY($side, true)];

                for ($i = 0; $i <= 20; $i++) {
                    $sidePos = $worldSpawn->getSide($side, $i);
                    $world->loadChunk($sidePos->getX() >> 4, $sidePos->getZ() >> 4);
                    
                    $centerBlock = $world->getBlockAt($sidePos->getX(), $bridgeY, $sidePos->getZ());
                    
                    if ($centerBlock->getTypeId() === BlockTypeIds::STAINED_CLAY) {
                        foreach ($perpendicular as $perpSide) {
                            for ($offset = 1; $offset <= 6; $offset++) {
                                $extendPos = $sidePos->getSide($perpSide, $offset);
                                $world->loadChunk($extendPos->getX() >> 4, $extendPos->getZ() >> 4);
                                
                                $targetPos = $world->getBlockAt($extendPos->getX(), $bridgeY, $extendPos->getZ())->getPosition();
                                $selection->addBlock($targetPos, $centerBlock);
                            }
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        if ($hasSumoMode) {
            AsyncBlockManager::executeSet(
                $selection,
                $world,
                fn() => $this->spawnCages($world)
            );
        } else {
            AsyncBlockManager::executeReplace(
                $selection,
                $this->getInteractableBlocks(),
                $world,
                fn() => $this->spawnCages($world)
            );
        }
    }

    /**
     * @return StainedHardenedClay[]
     */
    public function getInteractableBlocks(): array
    {
        return [
            VanillaBlocks::STAINED_CLAY(),
            VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::BLUE),
            VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::RED),
        ];
    }

    public function spawnCages(?World $world = null): void
    {
        $world ??= $this->getWorld();

        $this->getCageCosmetic()->spawnCages(
            $world,
            $this->generateCages($world),
            false,
            function (): void {
                $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
                    foreach ($this->getPlayers() as $player) {
                        $player->setNoClientPredictions(false);
                    }
                }), 20);
            }
        );
    }

    /**
     * @return Cage[]
     */
    private function generateCages(?World $world = null): array
    {
        return $this->cages ??= array_map(function (BridgeTeam $team) use ($world): Cage {
            return $team->generateCage($world);
        }, $this->getAliveTeams());
    }

    public function startGame(): void
    {
        $this->goalLimit = $this->isPrivateGame() ? $this->getGameSettings()->getGoalLimit() : ($this->getType() === self::TYPE_RUSH ? 3 : 5);

        $this->broadcastTitle(' ', '', 0, 120);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center('The Bridge'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Cross the bridge to score goals.'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Knock off your opponent to gain a clear path.'), true);
        if (($credits = $this->getPlugin()->getArenaConfig()->getCredits($this)) !== null) {
            $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . $this->getMapDisplayName() . ", by " . $credits), true);
        }
        $this->broadcastMessage('', true);
        if ($this->isSoloGame()) {
            $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'First player to score ' . $this->goalLimit . ' goals wins!'), true);
        } else {
            $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'First team to score ' . $this->goalLimit . ' goals wins!'), true);
        }
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        foreach ($this->getTeams() as $team) {
            foreach ($team->getAlivePlayers() as $player) {
                $this->getWorld()->addSound($player->getPosition(), new BlazeShootSound(), [$player]);
            }

            $team->sendScoreboard();
            $team->respawnAllPlayers(true);
        }

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ArenaTickTask($this), 2);
    }

    public function getScoreVisuals(BridgeTeam $team): string
    {
        $goals = $team->getScore();
        
        $teamPrefix = TextFormat::BOLD . $team->getColor() . '[' . ucwords(substr($team->getName(), 0, 1)) . '] ' . TextFormat::RESET;

        if ($this->goalLimit > 5) {
            return $teamPrefix . $team->getColor() . $goals . TextFormat::GRAY . '/' . TextFormat::WHITE . $this->goalLimit;
        }

        $visual = '';
        for ($i = 1; $i <= $this->goalLimit; $i++) {
            if ($i <= $goals) {
                $visual .= $team->getColor() . '●';
            } else {
                $visual .= TextFormat::GRAY . '●';
            }
        }

        return $teamPrefix . $visual;
    }

    public function getStreaksKey(): ?string
    {
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    /**
     * @return BridgeTeam[]
     */
    public function getAliveTeams(): array
    {
        /** @var BridgeTeam[] $aliveTeams */
        $aliveTeams = parent::getAliveTeams();

        return $aliveTeams;
    }

    private function getCageCosmetic(): CagesCosmetic
    {
        return $this->isSoloGame() ? CosmeticHandler::SOLO_CAGES() : CosmeticHandler::TEAM_CAGES();
    }

    public function removeCages(): void
    {
        $this->getCageCosmetic()->despawnCages($this->getWorld());
    }

    public function canScoreGoal(): bool
    {
        return $this->phase === self::PHASE_RUN;
    }

    public function sendStats(): void
    {
        $this->getStatsData()->sendLeaderboard($this, StatsData::TB_GOALS, '§l§aTOP SCORERS');
    }

    public function getBuildHeight(): int
    {
        return $this->buildHeight;
    }

    public function addKill(Player $player, Player $victim): void
    {
        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::TB_KILLS);
        $statsData->addKill($player, $victim, StatsData::TB_MODE_KILLS);

        $this->getScoreboard()->setLine([$player], 7, CustomIcon::KILLS . TextFormat::GREEN . $statsData->getValue($player, StatsData::TB_KILLS));

        $this->playKillCosmetics($player);

        $combatLog = $this->getPlugin()->getEssentials()->getCombatLogger()->getLog($victim);
        foreach ($combatLog->getAssists() as $assist) {
            if (($playerAssist = $this->getPlugin()->getServer()->getPlayerExact($assist)) === null || $playerAssist === $player) {
                continue;
            }
            $statsData->addValue($playerAssist, StatsData::TB_KILL_ASSISTS);
            $statsData->addValue($playerAssist, StatsData::TB_MODE_KILL_ASSISTS);
        }
    }

    public function addGoal(Player $player): void
    {
        $statsData = $this->getStatsData();
        $statsData->addValue($player, StatsData::TB_GOALS);
        $statsData->addValue($player, StatsData::TB_MODE_GOALS);

        $this->getScoreboard()->setLine([$player], 6, CustomIcon::TARGET . TextFormat::GREEN . $statsData->getValue($player, StatsData::TB_GOALS));
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = true): void
    {
        $statsData = $this->getStatsData();

        /** @var BridgeTeam|null $team */
        $team = $this->getTeamNull($player);
        $opponent = $this->getOtherTeam($team);

        $modeId = $this->getModeId();

        $perfect = $team->getScore() >= $this->goalLimit && $opponent->getScore() === 0 && !$this->hasSamePartyOpponents($player) && !$this->ranOutOfTime;

        if (($goals = $statsData->getValue($player, StatsData::TB_GOALS)) > 0) {
            $data[self::DATA_XP][] = [
                $goals . ' Goal' . ($goals > 1 ? 's' : ''),
                $goals * 3
            ];

            $data[self::DATA_CREDITS][] = [
                $goals . ' Goal' . ($goals > 1 ? 's' : ''),
                $goals * (match ($modeId) {
                    self::MODE_SOLO => 2,
                    self::MODE_DOUBLES => 4,
                    default => 2
                })
            ];

            if ($perfect) {
                $data[self::DATA_XP][] = [
                    'Perfect Game',
                    3
                ];
            }
        }

        if ($this->isWinner($player)) {
            if ($perfect) {
                $data[self::DATA_CREDITS][] = [
                    'Perfect Game',
                    (match ($modeId) {
                        self::MODE_SOLO => 8,
                        self::MODE_DOUBLES => 11,
                        default => 8
                    })
                ];
            } else {
                $data[self::DATA_CREDITS][] = [
                    'Win',
                    (match ($modeId) {
                        self::MODE_SOLO => 4,
                        self::MODE_DOUBLES => 8,
                        default => 4
                    })
                ];
            }
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    /**
     * @param Player $player
     * @return BridgeTeam
     */
    public function getTeam(Player $player): Team
    {
        /** @var BridgeTeam $team */
        $team = parent::getTeam($player);

        return $team;
    }

    public function getOtherTeam(BridgeTeam $team): BridgeTeam
    {
        $teams = $this->getTeams();
        unset($teams[array_search($team, $teams, true)]);

        return $teams[array_key_first($teams)];
    }

    public function setRanOutOfTime(bool $ranOutOfTime): void
    {
        $this->ranOutOfTime = $ranOutOfTime;
    }

    /**
     * @return BridgeSettings
     */
    public function getGameSettings(): GameSettings
    {
        /** @var BridgeSettings $settings */
        $settings = parent::getGameSettings();

        return $settings;
    }
}
