<?php
declare(strict_types=1);

namespace libminigames\utils\streaks;

class Streak
{
    private string $xuid;
    private string $gameKey;
    private int $current;
    private int $best;
    private bool $bestChanged;

    /**
     * @param string $xuid
     * @param string $gameKey
     * @param int $current
     * @param int $best
     * @param bool $bestChanged
     */
    public function __construct(string $xuid, string $gameKey, int $current, int $best, bool $bestChanged = false)
    {
        $this->xuid = $xuid;
        $this->gameKey = $gameKey;
        $this->current = $current;
        $this->best = $best;
        $this->bestChanged = $bestChanged;
    }

    /**
     * @param string[] $row
     * @return Streak
     */
    public static function FromSQL(array $row): Streak
    {
        return new Streak($row["xuid"], $row["gameKey"], (int)$row["current"], (int)$row["best"], array_key_exists("bestChanged", $row) && (bool)$row["bestChanged"]);
    }

    /**
     * @return string
     */
    public function getXuid(): string
    {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getGameKey(): string
    {
        return $this->gameKey;
    }

    /**
     * @return int
     */
    public function getCurrent(): int
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function getBest(): int
    {
        return $this->best;
    }

    /**
     * @return bool
     */
    public function isBestChanged(): bool
    {
        return $this->bestChanged;
    }
}