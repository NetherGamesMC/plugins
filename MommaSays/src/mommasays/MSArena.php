<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays;

use libminigames\Arena;
use libminigames\Minigame;
use mommasays\games\Game;
use mommasays\tasks\CountDownTask;
use mommasays\tasks\MatchTimeTask;
use mommasays\utils\CageSpawner;
use mommasays\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\block\utils\DyeColor;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\World;
use function arsort;
use function get_class;

class MSArena extends Arena
{
    public const MODE_SIMON_SAYS = 0;

    /** @var int[] */
    private array $points = [];
    /** @var int[] */
    private array $gamesWon = [];
    /** @var Game|null */
    private ?Game $game = null;
    /** @var string[] */
    private array $playedGames = [];
    /** @var Vector3 */
    private Vector3 $winnerSpawn;
    /** @var Vector3 */
    private Vector3 $loserSpawn;

    public function __construct(MommaSays $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);

        $this->listener = new MSArenaListener($this);
        $this->statsData = new StatsData($plugin->getModeName($modeId));
        $this->maps = ['MommaSays'];
    }

    public function bootMinigame(): void
    {
        $this->getWorld()->setSpawnLocation(new Vector3(1, 41, -1));
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    /**
     * @return MommaSays
     */
    public function getPlugin(): Minigame
    {
        /** @var MommaSays $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    public function setupMapFeatures(World $world): void
    {
        parent::setupMapFeatures($world);

        $spawner = new CageSpawner($this);
        $spawner->buildCage(new Vector3(4, 69, -4), $world, DyeColor::GREEN);
        $spawner->buildCage(new Vector3(-10, 69, -4), $world, DyeColor::RED);
    }

    public function getCurrentGame(): ?Game
    {
        return $this->game;
    }

    public function setCurrentGame(Game $game): void
    {
        $this->game = $game;
    }

    public function addToPlayedGame(Game $game): void
    {
        $this->playedGames[] = get_class($game);
    }

    public function getNotPlayedMinigame(bool $teleport): Game
    {
        $availableGames = [];

        foreach (Game::MINIGAMES as $game) {
            if (!in_array($game, $this->playedGames, true)) {
                $availableGames[] = $game;
            }
        }

        if (count($availableGames) === 0) {
            $list = Game::MINIGAMES;
            return new $list[array_rand($list)]($this, $teleport);
        }

        return new $availableGames[array_rand($availableGames)]($this, $teleport);
    }

    public function increasePoints(Player $player): void
    {
        $points = $this->points[$player->getName()] ?? 0;
        $this->points[$player->getName()] = ++$points;
    }

    public function getGamesWon(Player $player): int
    {
        return $this->gamesWon[$player->getName()] ?? 0;
    }

    public function increaseGamesWon(Player $player): void
    {
        $games = $this->gamesWon[$player->getName()] ?? 0;
        $this->gamesWon[$player->getName()] = ++$games;
    }

    public function sendStats(): void
    {
        $leaderboard = array_values($this->getLeaderboard());

        if (count($leaderboard) !== 0) {
            $this->broadcastMessage('§e§l----------------------------', true);
            $this->broadcastMessage('§l§aTOP SCORERS', true);
            $this->broadcastMessage(CustomIcon::TROPHY_GOLD . "§r§7- §b" . TextFormat::clean($leaderboard[0][0]) . ' - §e' . $leaderboard[0][1], true);
            if (isset($leaderboard[1])) {
                $this->broadcastMessage(CustomIcon::TROPHY_SILVER . '§r§7- §b' . TextFormat::clean($leaderboard[1][0]) . ' - §e' . $leaderboard[1][1], true);
            }
            if (isset($leaderboard[2])) {
                $this->broadcastMessage(CustomIcon::TROPHY_BRONZE . '§r§7- §b' . TextFormat::clean($leaderboard[2][0]) . ' - §e' . $leaderboard[2][1], true);
            }
            $this->broadcastMessage('§e§l----------------------------', true);
        }
    }

    public function getLeaderboard(): array
    {
        $points = $this->points;
        arsort($points);
        $results = [];

        /**
         * @var string $player
         * @var int $point
         */
        foreach ($points as $player => $point) {
            if (($onlinePlayer = $this->getPlugin()->getServer()->getPlayerExact($player)) instanceof Player) {
                $results[$player] = [$onlinePlayer->getDisplayName(), $point];
            }
        }

        return $results;
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        if ($this->getPoints($player) === MatchTimeTask::GAMES_PLAYING) {
            $data[self::DATA_XP][] = [
                'Perfect Run',
                5
            ];

            $data[self::DATA_CREDITS][] = [
                'Perfect Run',
                8
            ];
        }

        if ($this->isWinner($player)) {
            $data[self::DATA_CREDITS][] = [
                'Win',
                6
            ];
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    /**
     * @param Player|null $player
     * @return int|int[]
     */
    public function getPoints(?Player $player = null): array|int
    {
        if ($player === null) {
            return $this->points;
        }

        return $this->points[$player->getName()] ?? 0;
    }

    /**
     * @internal
     */
    public function startGame(): void
    {
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center('Momma Says'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Follow the instructions to gain points.'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'The player with the most points wins!'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        $spawn = new Location(Game::ARENA_SPAWN_POINT[0], Game::ARENA_SPAWN_POINT[1], Game::ARENA_SPAWN_POINT[2], $this->getWorld(), 0.0, 0.0);
        /** @var NGPlayer $player */
        foreach ($this->getPlayers() as $player) {
            $player->setEnergized();
            $player->teleport($spawn);

            $this->points[$player->getName()] = 0;

            /** @var NGPlayer[] $players */
            $players = array_values(array_diff($this->getPlayers(), [$player]));
            if (count($players) >= 2) {
                $this->getScoreboard()->setLines([$player], [
                    8 => '',
                    7 => CustomIcon::TROPHY_GOLD . $player->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . 0,
                    6 => CustomIcon::TROPHY_SILVER . $players[0]->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . 0,
                    5 => CustomIcon::TROPHY_BRONZE . $players[1]->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . 0,
                    4 => '',
                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . 0 . '/' . MatchTimeTask::GAMES_PLAYING,
                    2 => '',
                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                ]);
            } elseif (count($players) === 1) {
                $this->getScoreboard()->setLine([$player], 8, '');
                $this->getScoreboard()->setLines([$player], [
                    7 => '',
                    6 => CustomIcon::TROPHY_GOLD . $player->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . 0,
                    5 => CustomIcon::TROPHY_SILVER . $players[0]->getNameTag() . TextFormat::GRAY . ' ' . TextFormat::GREEN . 0,
                    4 => '',
                    3 => CustomIcon::GAMEMODE . TextFormat::GREEN . 0 . '/' . MatchTimeTask::GAMES_PLAYING,
                    2 => '',
                    1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
                ]);
            }

            $this->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
        }

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
    }

    public function getMinimumPlayers(): int
    {
        return 2;
    }

    public function getWinnerSpawn(): ?Vector3
    {
        return $this->winnerSpawn;
    }

    public function setWinnerSpawn(Vector3 $vector3): void
    {
        $this->winnerSpawn = $vector3;
    }

    public function getLoserSpawn(): ?Vector3
    {
        return $this->loserSpawn;
    }

    public function setLoserSpawn(Vector3 $vector): void
    {
        $this->loserSpawn = $vector;
    }

    public function getMaxSize(): int
    {
        return 12;
    }

    public function getWaitingLobbySpawn(): Location
    {
        /** @var World $world */
        $world = $this->world ?? $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();

        return new Location(-24.5, 50.5, -25.5, $world, 315, 0.0);
    }
}