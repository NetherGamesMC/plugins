<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames;

use libminigames\settings\GameSettings;
use pocketmine\player\Player;
use pocketmine\plugin\PluginException;
use function array_filter;
use function array_merge;
use function array_unique;
use function count;
use function in_array;

abstract class TeamArena extends Arena
{
    public const MODE_SOLO = 1;
    public const MODE_DOUBLES = 2;
    public const MODE_TRIOS = 3;
    public const MODE_SQUADS = 4;

    /** @var Team[] */
    protected array $teams = [];
    /** @phpstan-var array<string, array<int>> */
    private array $parties = [];

    public function __construct(
        Minigame      $plugin,
        int           $modeId,
        int           $id,
        bool          $privateGame = false,
        ?GameSettings $settings = null
    )
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, $settings);

        $this->getGameSettings()->setTeamChangingAllowed(!$this->isSoloGame() || $this->isPrivateGame());
    }

    public function areTeamsBalanced(): bool
    {
        $lowestTeam = $this->getTeamSize();
        $highestTeam = 0;

        foreach ($this->getTeams() as $team) {
            $teamSize = $team->getSize();
            if ($teamSize > $highestTeam) {
                $highestTeam = $teamSize;
            }
            if ($teamSize < $lowestTeam) {
                $lowestTeam = $teamSize;
            }
        }

        return $lowestTeam >= $highestTeam - 1;
    }

    public function getTeamSize(): int
    {
        return match ($this->getModeId()) {
            self::MODE_DOUBLES => 2,
            self::MODE_TRIOS => 3,
            self::MODE_SQUADS => 4,
            default => 1,
        };
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return $this->teams;
    }

    public function getMinimumPlayers(): int
    {
        return 2 * $this->getTeamSize();
    }

    public function getMaxSize(): int
    {
        return count($this->getTeams()) * $this->getTeamSize();
    }

    public function getInitialActiveTeamCount(): int
    {
        return count(array_filter($this->getTeams(), static fn(Team $team): bool => $team->getInitialPlayerCount() > 0));
    }

    /**
     * @inheritDoc
     */
    public function isOpponentlessGame(): bool
    {
        return $this->getInitialActiveTeamCount() === 1;
    }

    public function getTeam(Player $player): Team
    {
        foreach ($this->getTeams() as $team) {
            if (in_array($player, $team->getPlayers(), true)) {
                return $team;
            }
        }

        throw new PluginException("Player wasn't in a team.");
    }

    /**
     * @param bool $includeSpectators
     * @return Player[]
     */
    public function getPlayers(bool $includeSpectators = true): array
    {
        $players = [];

        foreach ($this->getTeams() as $team) {
            foreach ($team->getPlayers() as $player) {
                $players[] = $player;
            }
        }

        if ($includeSpectators) {
            return array_unique(array_merge($players, $this->getSpectators()));
        }

        return $players;
    }

    public function getTeamNull(Player $player): ?Team
    {
        foreach ($this->getTeams() as $team) {
            if (in_array($player, $team->getPlayers(), true)) {
                return $team;
            }
        }

        return null;
    }

    /**
     * Returns the team that had or has someone with that xuid playing.
     *
     * @param string $xuid
     * @return Team|null
     */
    public function getTeamByXuid(string $xuid): ?Team
    {
        foreach ($this->getTeams() as $team) {
            if (in_array($xuid, $team->getXuids(), true)) {
                return $team;
            }
        }

        return null;
    }

    /**
     * All xuids of the players who are or were in the arena.
     *  The key is the player name, and the value is the xuid.
     *
     * @return array<string, string>
     */
    public function getXuids(): array
    {
        $xuids = [];

        foreach ($this->getTeams() as $team) {
            foreach ($team->getXuids() as $playerName => $xuid) {
                $xuids[$playerName] = $xuid;
            }
        }

        return $xuids;
    }

    public function start(): void
    {
        parent::start();

        $partyManager = $this->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();

        foreach ($this->getTeams() as $team) {
            foreach ($team->getPlayers() as $player) {
                if (($party = $partyManager->getParty($player)) !== null) {
                    $leaderName = $party->getLeaderName();

                    if (!isset($this->parties[$leaderName])) {
                        $this->parties[$leaderName] = [$team->getId()];
                    } elseif (!in_array($team->getId(), $this->parties[$leaderName])) {
                        $this->parties[$leaderName][] = $team->getId();
                    }
                }
            }
        }
    }

    /**
     * @return Team[]
     */
    public function getAliveTeams(): array
    {
        return array_values(array_filter($this->getTeams(), static function (Team $team): bool {
            return count($team->getAlivePlayers()) > 0;
        }));
    }

    /**
     * @return Player[]
     */
    public function getAlivePlayers(): array
    {
        $players = [];

        foreach ($this->getAliveTeams() as $team) {
            foreach ($team->getAlivePlayers() as $player) {
                $players[] = $player;
            }
        }

        return $players;
    }

    public function hasSamePartyOpponents(Player $player): bool
    {
        $partyManager = $this->getPlugin()->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();

        if (($party = $partyManager->getParty($player)) !== null) {
            return count($this->parties[$party->getLeaderName()] ?? []) > 1;
        }

        return false;
    }

    public function isSoloGame(): bool
    {
        return $this->getModeId() === self::MODE_SOLO;
    }
}