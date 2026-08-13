<?php

namespace NetherGames\NGEssentials\player\combatlogger;

class CombatLog
{
    public const MINIMUM_ASSIST_DAMAGE = 4;

    /** @var CombatHit[] */
    private array $hits = [];
    private CombatHit $lastHit;

    public function addHit(CombatHit $hit): void
    {
        $this->hits[] = $hit;
        $this->lastHit = $hit;
    }

    public function wipeLog(): void
    {
        $this->hits = [];
    }

    /**
     * Returns the names of the players that assisted in the kill.
     *
     * @return string[]
     */
    public function getAssists(): array
    {
        $assists = [];

        if (($lastHit = $this->getLatestHit()?->getDamagerName()) === null) {
            return [];
        }

        foreach ($this->getDamagePerPlayer() as $playerId => $damage) {
            if ($damage >= self::MINIMUM_ASSIST_DAMAGE && $playerId !== $lastHit) {
                $assists[] = $playerId;
            }
        }

        return $assists;
    }

    public function getLatestHit(): ?CombatHit
    {
        return $this->lastHit ?? null;
    }

    /**
     * @return array<string, float>
     */
    private function getDamagePerPlayer(): array
    {
        $damage = [];

        foreach ($this->hits as $hit) {
            $damagerName = $hit->getDamagerName();

            $damage[$damagerName] ??= 0;
            $damage[$damagerName] += $hit->getDamage();
        }

        return $damage;
    }
}