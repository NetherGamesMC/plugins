<?php

namespace meltdown\arena\handler;

use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\scoreboard\Scoreboard;
use pocketmine\utils\TextFormat;
use meltdown\arena\MDArena;
use meltdown\Meltdown;
use function count;

class ScoreboardHandler
{
    /** @var MDArena */
    private MDArena $arena;

    /**
     * @param MDArena $arena
     */
    public function __construct(MDArena $arena)
    {
        $this->arena = $arena;
    }

    public function setFirstScoreboard(): void
    {
        $lines = [
            8 => "",
            7 => CustomIcon::HOURGLASS . TextFormat::GREEN . date("i:s", Meltdown::$PLAYING_TIME),
            6 => "",
            5 => CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($this->getArena()->getAlivePlayers()),
            4 => "",
            3 => CustomIcon::MAP . TextFormat::GREEN . $this->getArena()->getMapDisplayName(),
            2 => "",
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . "ngmc.co"
        ];
        $this->getScoreboard()->setLines($this->getArena()->getPlayers(), $lines);
    }

    /**
     * @return MDArena
     */
    public function getArena(): MDArena
    {
        return $this->arena;
    }

    /**
     * @return Scoreboard
     */
    public function getScoreboard(): Scoreboard
    {
        return $this->getArena()->getScoreboard();
    }

    public function updatePlayerCount(): void
    {
        $this->setLine(5, CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($this->getArena()->getAlivePlayers()));
    }

    /**
     * @param int $lineNumber
     * @param string $text
     */
    public function setLine(int $lineNumber, string $text): void
    {
        $this->getScoreboard()->setLine($this->getArena()->getPlayers(), $lineNumber, $text);
    }

    /**
     * @param int $time
     * @param bool $overtime
     * @param bool $cooldown
     */
    public function updateTime(int $time, bool $overtime = false, bool $cooldown = false): void
    {
        $formattedTime = date("i:s", $time);
        if ($overtime) {
            $this->setLine(7, CustomIcon::HOURGLASS . TextFormat::RED . $formattedTime . " (OVERTIME)");
        } else if ($cooldown) {
            $this->setLine(7, CustomIcon::HOURGLASS . TextFormat::YELLOW . "Starting in: " . TextFormat::WHITE . $formattedTime);
        } else {
            $this->setLine(7, CustomIcon::HOURGLASS . TextFormat::GREEN . $formattedTime);
        }
    }
}