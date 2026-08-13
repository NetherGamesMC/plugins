<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer;

use libminigames\Team;
use libminigames\TeamArena;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function shuffle;

class SCTeam extends Team
{
    /** @var int */
    private int $score = 0;

    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param Vector3 $vector3
     * @return bool
     */
    public function isInGoal(Vector3 $vector3): bool
    {
        $x = $vector3->getX();
        $z = $vector3->getZ();

        return (1314 <= $z) && ($z <= 1325) && ($this->getId() === self::DARK_BLUE ? ((304 <= $x) && ($x <= 307)) : ((251 <= $x) && ($x <= 255)));
    }

    public function addGoal(Player $player, bool $wrongGoal = false): void
    {
        $arena = $this->getArena();

        if ($wrongGoal) {
            $arena->getOtherTeam($this)->addScore();

            $arena->removeGoal($player);

            $scoreText = $this->getArena()->getScore($this);
            $this->getArena()->broadcastTitle($this->getPlayerName($player) . " scored in the wrong goal!", $scoreText, 0, 60, 20);
            $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);
            $arena->broadcastMessage('', true);
            $arena->broadcastMessage(TextUtils::center($this->getPlayerName($player) . TextFormat::RED . ' scored in the wrong goal!'), true);
            $arena->broadcastMessage(TextUtils::center($scoreText), true);
            $arena->broadcastMessage('', true);
            $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);
        } else {
            $this->addScore();

            $this->getArena()->addGoal($player);

            $scoreText = $this->getArena()->getScore($this);
            $this->getArena()->broadcastTitle($this->getPlayerName($player) . " scored!", $this->getArena()->getScore($this), 0, 60, 20);
            $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);
            $arena->broadcastMessage('', true);
            $arena->broadcastMessage(TextUtils::center($this->getPlayerName($player) . TextFormat::YELLOW . ' scored!'), true);
            $arena->broadcastMessage(TextUtils::center($scoreText), true);
            $arena->broadcastMessage('', true);
            $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);
        }

        $scoreboard = $this->getArena()->getScoreboard();
        foreach ($this->getArena()->getAliveTeams() as $team) {
            $scoreboard->setLine($team->getPlayers(), 5, $this->getArena()->getScore($team));
            $team->teleportToSpawn();
        }

        $this->getArena()->spawnBall();
    }

    /**
     * @return SCArena
     */
    public function getArena(): TeamArena
    {
        /** @var SCArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function addScore(): void
    {
        $this->score++;
    }

    public function teleportToSpawn(): void
    {
        $players = $this->getPlayers();
        shuffle($players);

        foreach ($players as $id => $player) {
            $spawn = $this->getSpawn($id);

            $player->teleport(new Location($spawn->getX(), $spawn->getY(), $spawn->getZ(), $this->getArena()->getWorld(), $spawn->getYaw(), 0.0));
        }
    }

    public function getSpawn(int $id): Location
    {
        switch ($id) {
            case 0:
                return $this->getId() === self::DARK_BLUE ? new Location(290.5, 54, 1319.5, null, 90, 0) : new Location(268.5, 54, 1319.5, null, 270, 0);
            case 1:
                return $this->getId() === self::DARK_BLUE ? new Location(302.5, 54, 1319.5, null, 90, 0) : new Location(256.5, 54, 1319.5, null, 270, 0);
            case 2:
                return $this->getId() === self::DARK_BLUE ? new Location(295.5, 54, 1327.5, null, 90, 0) : new Location(264.5, 54, 1327.5, null, 270, 0);
            default:
                return $this->getId() === self::DARK_BLUE ? new Location(295.5, 54, 1311.5, null, 90, 0) : new Location(264.5, 54, 1311.5, null, 270, 0);
        }
    }
}