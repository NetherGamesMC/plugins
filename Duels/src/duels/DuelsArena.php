<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
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

namespace duels;

use duels\tasks\CountDownTask;
use duels\tasks\MatchTimeTask;
use duels\utils\Items;
use duels\utils\Kits;
use duels\utils\StatsData;
use libminigames\Minigame;
use libminigames\Team;
use libminigames\TeamArena;
use libminigames\utils\BlockCollector;
use libminigames\utils\TypeArena;
use libminigames\utils\TypeArenaTrait;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use libminigames\settings\GameSettings;
use function array_keys;
use function array_rand;
use function count;
use function in_array;
use function min;
use function round;

class DuelsArena extends TeamArena implements TypeArena
{
    use TypeArenaTrait;

    public const TYPE_NORMAL = 0;
    public const TYPE_IRON_SOUP = 1;
    public const TYPE_BOW = 2;
    public const TYPE_INSANE = 3;
    public const TYPE_OVERPOWERED = 4;
    public const TYPE_GAPPLE = 5;
    public const TYPE_NO_DEBUFF = 6;
    public const TYPE_COMBO = 7;
    public const TYPE_SUMO = 8;
    public const TYPE_BUILDUHC = 9;

    /** @var int[] */
    public array $kills = [];

    private BlockCollector $blockCollector;

    public function __construct(Duels $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, new DuelsSettings());

        $this->listener = new DuelsArenaListener($this);
        $this->blockCollector = new BlockCollector();
        $this->statsData = new StatsData($plugin->getModeName($modeId));

        $this->teams = [
            0 => new DuelsTeam($this, DuelsTeam::RED),
            1 => new DuelsTeam($this, DuelsTeam::DARK_BLUE),
        ];

        $arenas = $this->getPlugin()->getMaps(false, !$privateGame);
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
     * @return Duels
     */
    public function getPlugin(): Minigame
    {
        /** @var Duels $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_NORMAL => "Normal",
            self::TYPE_IRON_SOUP => "Iron Soup",
            self::TYPE_BOW => "Bow",
            self::TYPE_INSANE => "Insane",
            self::TYPE_OVERPOWERED => "Overpowered",
            self::TYPE_GAPPLE => "Gapple",
            self::TYPE_NO_DEBUFF => "No Debuff",
            self::TYPE_COMBO => "Combo",
            self::TYPE_SUMO => "Sumo",
            self::TYPE_BUILDUHC => "BuildUHC",
        ];
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = true): void
    {
        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                'Win',
                (match($this->getModeId()) {
                    self::MODE_SOLO => 4,
                    self::MODE_DOUBLES => 6,
                    default => 4
                })
            ];
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        parent::resetPlayer($player);

        if ($this->isRunning() || $this->isFinishing()) {
            $player->setHealthTag(false);
        }
    }

    public function getStreaksKey(): ?string
    {
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    /**
     * @param Player $player
     * @return DuelsTeam
     */
    public function getTeam(Player $player): Team
    {
        /** @var DuelsTeam $team */
        $team = parent::getTeam($player);

        return $team;
    }

    public function addKill(Player $player, Player $victim): void
    {
        if ($this->getGameSettings()->hasHealOnKill()) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::INSTANT_HEALTH(), amplifier: 10));
        }

        if ($this->getGameSettings()->hasRekitOnKill()) {
            Kits::giveKit($player, $this->getType());
        }

        $playerName = $this->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player);
        $kills = $this->kills[$playerName] ?? 0;
        $this->kills[$playerName] = ++$kills;

        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::KILLS);
        $statsData->addKill($player, $victim, StatsData::DUELS_KILLS);

        $this->playKillCosmetics($player);

        $combatLog = $this->getPlugin()->getEssentials()->getCombatLogger()->getLog($victim);
        foreach ($combatLog->getAssists() as $assist) {
            if (($playerAssist = $this->getPlugin()->getServer()->getPlayerExact($assist)) === null || $playerAssist === $player) {
                continue;
            }
            $statsData->addValue($playerAssist, StatsData::DUELS_KILL_ASSISTS);
        }
    }

    public function bootMinigame(): void
    {
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if ($this->isWaiting()) {
            $inventory = $player->getInventory();
            if ($this->isSoloGame()) {
                $inventory->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getTypeSelectionAnvil());
            } else {
                $inventory->setItem(Items::EXTRA_ITEM_1, Items::getTypeSelectionAnvil());
            }
        }
    }

    public function startGame(): void
    {
        $arenaConfig = $this->getPlugin()->getArenaConfig();

        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center($this->getTypeName() . ' Duel'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Eliminate your opponents!'), true);
        if (($credits = $arenaConfig->getCredits($this)) !== null) {
            $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . $this->getMapDisplayName() . ", by " . $credits), true);
        }
        $this->broadcastMessage('', true);

        if ($this->isSoloGame()) {
            foreach ($this->getAliveTeams() as $aliveTeam) {
                $opponent = '';
                foreach ($this->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getAlivePlayers() as $player) {
                        $opponent = $team->getPlayerName($player);
                    }
                    break;
                }
                foreach ($aliveTeam->getPlayers() as $player) {
                    $player->sendMessage(TextUtils::center(TextFormat::WHITE . TextFormat::BOLD . 'Opponent: ' . TextFormat::RESET . $opponent));
                }
            }
        } else {
            foreach ($this->getAliveTeams() as $aliveTeam) {
                $opponent = ['', ''];
                foreach ($this->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getAlivePlayers() as $player) {
                        $opponent[$key++] = $team->getPlayerName($player);
                    }
                    break;
                }
                foreach ($aliveTeam->getPlayers() as $player) {
                    $player->sendMessage(TextUtils::center(TextFormat::WHITE . TextFormat::BOLD . 'Opponents: ' . TextFormat::RESET . $opponent[0] . TextFormat::WHITE . ' & ' . TextFormat::RESET . $opponent[1]));
                }
            }
        }
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        foreach ($this->getAliveTeams() as $aliveTeam) {
            /** @var Location $spawn */
            $spawn = $arenaConfig->getTeamSpawn($this, $aliveTeam->getId());

            foreach ($aliveTeam->getPlayers() as $player) {
                /** @var NGPlayer $player */
                Kits::giveKit($player, $this->getType());

                $player->setHealthTag();

                if ($this->getType() !== self::TYPE_NO_DEBUFF) {
                    $player->setEnergized();
                }

                $this->getWorld()->addSound($player->getPosition(), new BlazeShootSound(), [$player]);
                $player->teleport($spawn);
            }
        }

        if ($this->isSoloGame()) {
            foreach ($this->getAliveTeams() as $aliveTeam) {
                $opponent = ['UNKNOWN', '0'];
                foreach ($this->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent = [$this->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $health];
                    }
                    break;
                }

                foreach ($aliveTeam->getPlayers() as $player) {
                    $this->getScoreboard()->setLines([$player], [
                            9 => '',
                            8 => CustomIcon::HOURGLASS . TextFormat::GREEN . '5:00',
                            7 => '',
                            6 => CustomIcon::PLAYERS_TINY,
                            5 => TextFormat::AQUA . $opponent[0] . TextFormat::GREEN . round((int)$opponent[1] / 2, 1) . CustomIcon::HEART,
                            4 => '',
                            3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->getTypeName(),
                            2 => '',
                            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                        ]
                    );
                }
            }
        } else {
            foreach ($this->getAliveTeams() as $aliveTeam) {
                $opponent = [['UNKNOWN', '0'], ['UNKNOWN', '0']];
                foreach ($this->getAliveTeams() as $team) {
                    if ($aliveTeam === $team) {
                        continue;
                    }
                    $key = 0;
                    foreach ($team->getPlayers() as $player) {
                        $health = $player->getHealth();
                        if (!in_array($player, $team->getAlivePlayers(), true)) {
                            $health = 0;
                        }
                        $opponent[$key++] = [$this->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player), $health];
                    }
                    break;
                }

                foreach ($aliveTeam->getPlayers() as $player) {
                    $this->getScoreboard()->setLines([$player], [
                            10 => '',
                            9 => CustomIcon::HOURGLASS . TextFormat::GREEN . '5:00',
                            8 => '',
                            7 => CustomIcon::PLAYERS_TINY,
                            6 => TextFormat::AQUA . $opponent[0][0] . TextFormat::GREEN . round((int)$opponent[0][1] / 2, 1) . CustomIcon::HEART,
                            5 => TextFormat::AQUA . $opponent[1][0] . TextFormat::GREEN . round((int)$opponent[1][1] / 2, 1) . CustomIcon::HEART,
                            4 => '',
                            3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $this->getTypeName(),
                            2 => '',
                            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co',
                        ]
                    );
                }
            }
        }

        if ($this->getType() === self::TYPE_BUILDUHC) {
            foreach ($this->getAlivePlayers() as $player) {
                $player->setGamemode(GameMode::SURVIVAL);
            }
        }

        $this->broadcastMessage('§eDuels has started! §cFIGHT!', true);

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
    }

    /**
     * @return DuelsTeam[]
     */
    public function getAliveTeams(): array
    {
        /** @var DuelsTeam[] $aliveTeams */
        $aliveTeams = parent::getAliveTeams();

        return $aliveTeams;
    }

    /**
     * @return DuelsTeam[]
     */
    public function getTeams(): array
    {
        /** @var DuelsTeam[] $teams */
        $teams = parent::getTeams();

        return $teams;
    }

    public function checkMapVotes(): void
    {
        if ($this->getType() === self::TYPE_SUMO) {
            $sumoMaps = $this->getPlugin()->getMaps(true, true);
            $this->mapName = $sumoMaps[array_rand($sumoMaps)];
        } else {
            parent::checkMapVotes();
        }
    }

    public function getBlockCollector(): BlockCollector
    {
        return $this->blockCollector;
    }

    /**
     * @return DuelsSettings
     */
    public function getGameSettings(): GameSettings
    {
        /** @var DuelsSettings $settings */
        $settings = parent::getGameSettings();

        return $settings;
    }

    public function getGXP(): int
    {
        return 3;
    }
}