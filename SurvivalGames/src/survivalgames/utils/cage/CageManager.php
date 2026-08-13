<?php

declare(strict_types=1);

namespace survivalgames\utils\cage;

use libasyncio\blocks\Selection;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\utils\Cage;
use NetherGames\NGEssentials\player\cosmetics\utils\SingleBlockCageGenerator;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use survivalgames\SGArena;
use function array_values;

class CageManager
{
    /** @var Selection|null */
    private static ?Selection $staticCages = null;

    /** @var SGArena */
    private SGArena $arena;
    /** @var array<int, Location> */
    private array $dataPosition = [];

    public function __construct(SGArena $arena)
    {
        $this->arena = $arena;
    }

    public function spawnCages(World $world): void
    {
        $arena = $this->arena;
        $arenaConfig = $arena->getPlugin()->getArenaConfig();

        $cosmetic = CosmeticHandler::SOLO_CAGES();
        $cages = [];

        foreach (array_values($arena->getAlivePlayers()) as $id => $player) {
            $location = $arenaConfig->getSpawn($arena, $world, $id);

            $this->dataPosition[$player->getId()] = $location;
            $cages[] = new Cage(
                self::getStaticCage(),
                $location
            );
        }

        $cosmetic->spawnCages($world, $cages, true);
    }

    public function respawnCages(World $world): void
    {
        $arena = $this->arena;

        $cosmetic = CosmeticHandler::SOLO_CAGES();
        $cages = [];

        foreach ($arena->getAlivePlayers() as $player) {
            $location = $this->dataPosition[$player->getId()] ?? throw new \RuntimeException('Player location not found');

            $cages[] = new Cage(
                self::getStaticCage(),
                $location
            );
        }

        $cosmetic->spawnCages($world, $cages, true);
    }

    public function despawnCages(): void
    {
        CosmeticHandler::SOLO_CAGES()->despawnCages($this->arena->getWorld());
    }

    /**
     * Returns the position of a player's cage.
     *
     * @param Player $player
     *
     * @return Position|null
     */
    public function getCagePosition(Player $player): ?Position
    {
        return $this->dataPosition[$player->getId()] ?? null;
    }

    /**
     * Attempt to teleport player back into their cages. Usually we will do this
     * when you want a deathmatch event. For spectators, they will be teleported to the arena's midpoint.
     */
    public function teleportToCages(): void
    {
        foreach ($this->arena->getPlayers() as $player) {
            if ($this->arena->isSpectator($player)) {
                $player->teleport($this->arena->getMidpoint());
            } else {
                $player->teleport($this->getCagePosition($player));
            }
        }
    }

    public static function getStaticCage(): Selection
    {
        // lazy init.
        return self::$staticCages ??= SingleBlockCageGenerator::generateCage(VanillaBlocks::INVISIBLE_BEDROCK(), 1);
    }
}