<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\worldfeatures\zones;

use NetherGames\NGEssentials\player\worldfeatures\WorldFeatures;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class ZonesManager
{
    /** @var Zone[] */
    private array $zones = [];

    public function __construct(WorldFeatures $features)
    {
        $plugin = $features->getPlugin();

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach ($this->zones as $zone) {
                $zone->tick();
            }
        }), 20);
    }

    public function addZone(Zone $zone): void
    {
        $this->zones[] = $zone;
    }

    public function getZone(Player $player): ?Zone
    {
        foreach ($this->zones as $zone) {
            if ($zone->isInZone($player)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * @return Zone[]
     */
    public function getZones(): array
    {
        return $this->zones;
    }
}