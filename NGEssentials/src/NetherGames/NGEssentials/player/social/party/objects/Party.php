<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\social\party\objects;

use JsonException;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\player\Player;
use function array_diff;
use function count;
use function in_array;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class Party
{
    public function __construct(
        private string $leaderName,
        /** @var string[] */
        private array  $members = [],
        private bool   $public = false,
        private bool   $privateGames = false,
        private bool   $playerRandomization = true,
    )
    {
    }

    public static function fromString(string $string): ?Party
    {
        try {
            $data = json_decode($string, true, 512, JSON_THROW_ON_ERROR);

            if (isset($data['leader'])) {
                return new Party($data['leader'], $data['members'] ?? [], $data['public'] ?? false, $data['private-games'] ?? false, $data['player-randomization'] ?? true);
            }
        } catch (JsonException $e) {

        }

        return null;
    }

    public function getLeader(): ?Player
    {
        return NGEssentials::getInstance()->getServer()->getPlayerExact($this->getLeaderName());
    }

    public function getLeaderName(): string
    {
        return $this->leaderName;
    }

    public function setLeader(Player $player): void
    {
        $this->leaderName = $player->getName();
    }

    public function addMember(string $playerName): bool
    {
        if (in_array($playerName, $this->members, true)) {
            return false;
        }

        $this->members[] = $playerName;
        return true;
    }

    public function removeMember(string $playerName): void
    {
        $this->members = array_diff($this->members, [$playerName]);
    }

    public function getTotalMembers(): int
    {
        return count($this->getMembers()) + 1;
    }

    /**
     * @return string[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * @return string[]
     */
    public function getAll(): array
    {
        $members = $this->getMembers();
        $members[] = $this->getLeaderName();

        return $members;
    }

    public function setPrivateGames(bool $privateGames): void
    {
        $this->privateGames = $privateGames;
    }

    public function setPlayerRandomization(bool $playerRandomization): void
    {
        $this->playerRandomization = $playerRandomization;
    }

    public function toString(): string
    {
        $data = [];

        $data['leader'] = $this->getLeaderName();
        if (count($members = $this->getMembers()) !== 0) {
            $data['members'] = $members;
        }
        if ($this->isPublic()) {
            $data['public'] = true;
        }
        if ($this->hasPrivateGames()) {
            $data['private-games'] = true;
        }
        if (!$this->hasPlayerRandomization()) {
            $data['player-randomization'] = false;
        }

        try {
            return (string)json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return '';
        }
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function hasPrivateGames(): bool
    {
        return $this->privateGames;
    }

    public function hasPlayerRandomization(): bool
    {
        return $this->playerRandomization;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }
}