<?php

namespace NetherGames\NGEssentials\player\combatlogger;

use pocketmine\player\Player;

class CombatHit
{
    /** @var int */
    private int $time;
    /** @var string */
    private string $damagerName;

    public function __construct(Player $damager, private float $damage)
    {
        $this->time = time();
        $this->damagerName = $damager->getName();
    }

    public function getDamagerName(): string
    {
        return $this->damagerName;
    }

    public function getDamage(): float
    {
        return $this->damage;
    }

    public function getTime(): int
    {
        return $this->time;
    }
}