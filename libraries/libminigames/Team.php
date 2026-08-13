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

use libminigames\utils\Items;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\block\utils\DyeColor;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_filter;
use function count;
use function substr;
use function ucwords;

class Team
{
    public const WHITE = 0;
    public const DARK_AQUA = 1;
    public const YELLOW = 2;
    public const GREEN = 3;
    public const DARK_BLUE = 4;
    public const RED = 5;
    public const DARK_GRAY = 6;
    public const LIGHT_PURPLE = 7;
    public const GOLD = 8;
    public const GRAY = 9;
    public const AQUA = 10;
    public const DARK_PURPLE = 11;

    public const TEAMS = [
        'White',
        'Cyan',
        'Yellow',
        'Green',
        'Blue',
        'Red',
        'Gray',
        'Pink',
        'Gold',
        'Silver',
        'Aqua',
        'Purple'
    ];

    public const COLORS = [
        TextFormat::WHITE,
        TextFormat::DARK_AQUA,
        TextFormat::YELLOW,
        TextFormat::GREEN,
        TextFormat::DARK_BLUE,
        TextFormat::RED,
        TextFormat::DARK_GRAY,
        TextFormat::LIGHT_PURPLE,
        TextFormat::GOLD,
        TextFormat::GRAY,
        TextFormat::AQUA,
        TextFormat::DARK_PURPLE,
    ];

    /** @var Player[] */
    protected array $players = [];
    /** @var int */
    protected int $id;
    /** @var array<string, string> */
    private array $xuids = [];
    /** @var TeamArena */
    private TeamArena $arena;

    public function __construct(TeamArena $arena, int $id)
    {
        $this->arena = $arena;
        $this->id = $id;
    }

    final public function isFull(): bool
    {
        return $this->getSize() === $this->getArena()->getTeamSize();
    }

    final public function getSize(): int
    {
        return count($this->getPlayers());
    }

    /**
     * @return Player[]
     */
    final public function getPlayers(): array
    {
        return $this->players;
    }

    public function getArena(): TeamArena
    {
        return $this->arena;
    }

    /**
     * All xuids of the players who are or were in the team.
     * The key is the player name, and the value is the xuid.
     *
     * @return array<string, string>
     */
    final public function getXuids(): array
    {
        return $this->xuids;
    }

    public function getInitialPlayerCount(): int
    {
        return count($this->getXuids());
    }

    public function addPlayer(Player $player, bool $teamChange = false): void
    {
        /** @var NGPlayer $player */
        $this->players[] = $player;

        if ($teamChange) {
            $player->setNameTag($this->getPlayerName($player, true));
        } else {
            $player->sendConditionalMessage('§6Searching for an available match...');
        }

        if (!in_array($player->getXuid(), $this->xuids, true)) {
            $playerManager = $this->getArena()->getPlugin()->getEssentials()->getPlayerManager();
            $this->xuids[$playerManager->getPlayerName($player)] = $player->getXuid();
        }
    }

    public function getPlayerName(Player $player, bool $nametag = false): string
    {
        if ($nametag) {
            return TextFormat::BOLD . $this->getColor() . ucwords(substr($this->getName(), 0, 1)) . TextFormat::RESET . ' ' . $this->getPlayerName($player);
        }

        return $this->getColor() . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($player);
    }

    final public function getColor(): string
    {
        return self::COLORS[$this->getId()];
    }

    final public function getId(): int
    {
        return $this->id;
    }

    final public function getName(): string
    {
        return self::TEAMS[$this->getId()];
    }

    final public function getDisplayName(): string
    {
        return $this->getColor() . $this->getName();
    }

    public function queuePlayer(Player $player): void
    {
        $inventory = $player->getInventory();
        $inventory->clearAll();
        $inventory->setHeldItemIndex(1);

        if ($this->getArena()->isSoloGame()) {
            if ($this->getArena()->isWaiting() && $this->getArena()->getPlugin()->hasWaitingLobby() && count($this->getArena()->getMaps()) > 1) {
                $inventory->setItem(Items::MAP_SELECTOR, Items::getMapSelectionPaper());
            }
        } else {
            if ($this->getArena()->isWaiting()) {

                if ($this->getArena()->getPlugin()->hasWaitingLobby() && count($this->getArena()->getMaps()) > 1) {
                    $inventory->setItem(Items::MAP_SELECTOR, Items::getMapSelectionPaper());
                }
            }
        }

        $inventory->setItem(Items::TEAM_SELECTOR, Items::getTeamSelectionWool($this->getDyeColor()));
        $inventory->setItem(Items::QUIT_BED, Items::getQuitBed());

        // If the player is the game creator, give them the ability to change the game's settings
        if ($this->getArena()->isCreator($player)) {
            $player->getInventory()->setItem(Items::PRIVATE_GAME_SETTINGS, Items::getGameSettingsBlazeRod());
            $player->getInventory()->setItem(Items::EXTRA_ITEM_2, Items::getManualStart());
        }

        $player->setNameTag($this->getPlayerName($player, true));
    }

    public function getDyeColor(): DyeColor
    {
        $map = [
            self::WHITE => DyeColor::WHITE,
            self::DARK_AQUA => DyeColor::CYAN,
            self::YELLOW => DyeColor::YELLOW,
            self::GREEN => DyeColor::LIME,
            self::DARK_BLUE => DyeColor::BLUE,
            self::RED => DyeColor::RED,
            self::DARK_GRAY => DyeColor::GRAY,
            self::LIGHT_PURPLE => DyeColor::PINK,
            self::GOLD => DyeColor::ORANGE,
            self::GRAY => DyeColor::LIGHT_GRAY,
            self::AQUA => DyeColor::LIGHT_BLUE,
            self::DARK_PURPLE => DyeColor::PURPLE,
        ];

        return $map[$this->getId()];
    }

    public function broadcastTitle(string $title, string $subtitle = '', int $fadeIn = 0, int $stay = 40, int $fadeOut = 0): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
        }
    }

    public function broadcastTip(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendTip($message);
        }
    }

    public function broadcastPopup(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendPopup($message);
        }
    }

    public function broadcastMessage(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->sendMessage($message);
        }
    }

    /**
     * @return Player[]
     */
    final public function getAlivePlayers(): array
    {
        $arena = $this->getArena();

        return array_values(array_filter($this->getPlayers(), static function (Player $player) use ($arena): bool {
            return !$arena->isSpectator($player);
        }));
    }

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        $this->players = array_diff($this->players, [$player]);

        $arena = $this->getArena();
        if ($teamChange || ($arena->isWaiting() || $arena->isStarting())) {
            $this->xuids = array_diff($this->xuids, [$player->getXuid()]);
        }
    }

    /**
     * Checks if a player can join this team. Returns null if true, otherwise returns string with an unformatted error message.$
     * @param int $amount The amount of players joining the team.
     */
    public function canJoinTeam(Player $player, int $amount): ?string
    {
        $arena = $this->getArena();

        if (($currentTeam = $arena->getTeamNull($player)) !== null && $this->getName() === $currentTeam->getName()) {
            return "You are already in this team!";
        } elseif ($arena->isPrivateGame()) {
            if (!$arena->getGameSettings()->isTeamChangingAllowed()) {
                return 'The host has disabled team changing!';
            }
        } else if ($arena->isSoloGame()) {
            return 'Can not change teams in solo mode!';
        } else if (!$player->hasPermission(Permissions::RANK_EMERALD)) {
            return '§l§aEMERALD §r§cor §l§bLEGEND §r§crank required to choose teams! Purchase at §bngmc.co/store§c!'; // Formatted for promotional purposes.
        } else if ($arena->isSpectator($player)) {
            return 'Spectators can not join teams!';
        } else if ($arena->getPlugin()->balanceQueuing() && $arena->areTeamsBalanced()) {
            return 'Teams are balanced, you cannot change teams.';
        } elseif ($this->getSize() + $amount > $arena->getTeamSize()) {
            return 'That team is full!';
        }

        return null;
    }
}
