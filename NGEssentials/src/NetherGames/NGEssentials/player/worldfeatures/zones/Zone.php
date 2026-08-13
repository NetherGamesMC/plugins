<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\worldfeatures\zones;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\World;
use function array_diff;
use function array_filter;

abstract class Zone
{
    /** @var Player[] */
    private array $players = [];

    public function __construct(
        private AxisAlignedBB $alignedBB,
        private World         $world
    )
    {
    }

    public function tick(): void
    {
        $players = array_filter($this->world->getNearbyEntities($this->alignedBB), static function (Entity $player): bool {
            return $player instanceof Player;
        });

        $outsidePlayers = array_diff($this->players, $players);
        foreach ($outsidePlayers as $player) {
            $this->leave($player);
        }

        $insidePlayers = array_diff($players, $this->players);
        foreach ($insidePlayers as $player) {
            $this->enter($player);
        }

        $this->players = $players;
    }

    abstract public function leave(Player $player): void;

    abstract public function enter(Player $player): void;

    public function getInsidePlayers(): array
    {
        return $this->players;
    }

    public function isInZone(Player $player): bool
    {
        return in_array($player, $this->players, true);
    }
}