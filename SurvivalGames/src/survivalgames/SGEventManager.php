<?php

declare(strict_types=1);

namespace survivalgames;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use survivalgames\task\MeteoriteSpawnTask;

class SGEventManager
{

    public const NONE = -1;
    public const ACID_WATER = 0;
    public const RAINY_DAY = 1;
    public const METEOR_SHOWER = 2;
    public const POTION_EFFECTS = 3;
    public const CREEPER_MANIA = 4;

    private const METEOR_RANGE = 45;

    /** @var SGArena */
    private SGArena $arena;

    /** @var int[] */
    private array $scenarios = []; // Pre-generated scenarios
    /** @var string[] */
    private array $availCases = [];
    /** @var int */
    private int $ticks = 0; // The tick of an event.
    /** @var int */
    private int $currentScenario = self::NONE;

    public function __construct(SGArena $arena)
    {
        $this->arena = $arena;

        $this->generateScenarios();
    }

    /**
     * Attempt to generate what is called as scenarios for events
     */
    private function generateScenarios(): void
    {
        $availCases = [];
        $scenarios = [];
        for ($i = 3; $i <= 10; $i++) {
            $scene = $i * 60;

            // Good case scenarios to worst case scenarios
            // 0: Rainy Day -> 1: Potion Effects -> 2: Cosmetics -> 3: Acidic Water -> 4: Meteor Shower -> 5: Creeper Mania
            // 5 -> 8 -> 9 -> 11 -> 15 -> 16

            // Add up 1-3 minutes grace period for the next event
            // Also, randomize each scenarios cases.
            switch (true) {
                case mt_rand(0, 7) === 7:
                    $availCases[self::RAINY_DAY] = "Rainy Day"; // Night In The Woods :3
                    $scenarios[$scene + mt_rand(0, 60)] = self::RAINY_DAY;

                    $i += mt_rand(0, 2);
                    break;
                case mt_rand(0, 11) === 11:
                    $availCases[self::ACID_WATER] = "Acidic Water";
                    $scenarios[$scene + mt_rand(0, 60)] = self::ACID_WATER;

                    $i += mt_rand(0, 2);
                    break;
                case mt_rand(0, 8) === 8;
                    $availCases[self::METEOR_SHOWER] = "Meteorite";
                    $scenarios[$scene + mt_rand(0, 60)] = self::METEOR_SHOWER;

                    $i += mt_rand(0, 2);
                    break;
                /*
                case mt_rand(0, 9) === 9:
                    $availCases[self::POTION_EFFECTS] = "Potion Effects";
                    $scenarios[$scene + mt_rand(0, 60)] = self::POTION_EFFECTS;

                    $i += mt_rand(0, 2);
                    break;
                case mt_rand(0, 13) === 13:
                    $availCases[self::CREEPER_MANIA] = "Creeper Mania";
                    $scenarios[$scene + mt_rand(0, 60)] = self::CREEPER_MANIA;

                    $i += mt_rand(0, 2);
                    break;
                */
                default:
                    $scenarios[$scene] = self::NONE;
                    break;
            }
        }

        $this->scenarios = $scenarios;
        $this->availCases = $availCases;
    }

    /**
     * @return string[]
     */
    public function getScenarios(): array
    {
        return $this->availCases;
    }

    public function getEvent(): int
    {
        return $this->currentScenario;
    }

    public function getEventScoreboard(int $gameTick): ?string
    {
        if ($this->currentScenario === self::NONE) {
            $sample = $this->getNearestEvent($gameTick, 31);

            if ($sample[1] !== self::NONE) {
                if (!$this->arena->hasFlags(SGArena::CAN_RAIN) && $sample[1] === self::RAINY_DAY) {
                    return null;
                }

                return $this->availCases[$sample[1]] . " in " . TextFormat::GREEN . $sample[0] . "s";
            }

            return null;
        }

        return $this->availCases[$this->currentScenario];
    }

    /**
     * Returns the nearest sample of an event within the game tick.
     *
     * @param int $gameTick
     * @param int $sample
     *
     * @return array
     */
    public function getNearestEvent(int $gameTick, int $sample): array
    {
        $lastTick = 0;
        foreach ($this->scenarios as $tick => $scenario) {
            if ($lastTick <= $gameTick && $gameTick <= $tick) {
                if (($tick - $gameTick) >= $sample) {
                    continue;
                }

                return [$tick - $gameTick, $scenario];
            }

            $lastTick = $tick;
        }

        return [$gameTick, self::NONE];
    }

    public function tickEvents(int $timePassed): void
    {
        if ($this->currentScenario === self::NONE) {
            $sample = $this->getNearestEvent($timePassed, 1);
            if ($sample[1] === self::NONE) {
                return;
            }
            if (!$this->arena->hasFlags(SGArena::CAN_RAIN) && $sample[1] === self::RAINY_DAY) {
                return;
            }

            $this->currentScenario = $sample[1];
            return; // Allow the event to tick in the next server tick.
        }

        $maxSample = 30;
        switch ($this->currentScenario) {
            case self::RAINY_DAY:
                if (!$this->arena->hasFlags(SGArena::IS_RAINING)) {
                    $this->setRaining(true);

                    $this->arena->setArenaFlag(SGArena::IS_RAINING, true);
                } else if ($this->ticks === $maxSample) {
                    $this->setRaining(false);
                }
                break;
            case self::ACID_WATER:
                if (!$this->arena->hasFlags(SGArena::ACIDIC_WATER)) {
                    $this->arena->setArenaFlag(SGArena::ACIDIC_WATER, true);
                } else if ($this->ticks === $maxSample) {
                    $this->arena->setArenaFlag(SGArena::ACIDIC_WATER, false);
                }
                break;
            case self::CREEPER_MANIA:
            case self::POTION_EFFECTS:
                // TODO: Implement better pathfinder (Probably multithreaded pathfinder?)
                break;
            case self::METEOR_SHOWER:
                $maxSample = 15;

                if ($this->ticks % 3 !== 0) {
                    break;
                }

                // Better implementation, use all alive players coordinates in order
                // to fire those meteor >:) more intense and dramatic
                foreach ($this->arena->getAlivePlayers() as $player) {
                    $vector = $player->getPosition();

                    $propCount = 0;
                    $meteorCount = rand(15, 24);
                    while ($propCount < $meteorCount) {
                        $xRand = mt_rand(-self::METEOR_RANGE, self::METEOR_RANGE) + $vector->getX();
                        $zRand = mt_rand(-self::METEOR_RANGE, self::METEOR_RANGE) + $vector->getZ();

                        $targetVec = new Vector3($xRand, $vector->getY() + 90, $zRand);
                        $this->arena->getPlugin()->getScheduler()->scheduleRepeatingTask(new MeteoriteSpawnTask($targetVec, $this->arena->getWorld()), 5);

                        $propCount++;
                    }
                }
                break;
        }

        if ($this->ticks === $maxSample) {
            $this->currentScenario = self::NONE;
            $this->ticks = 0;
        } else {
            $this->ticks++;
        }
    }

    private function setRaining(bool $isRaining): void
    {
        if ($isRaining) {
            $eventId = LevelEvent::START_RAIN;
            $eventData = 22000;
        } else {
            $eventId = LevelEvent::STOP_RAIN;
            $eventData = 0;
        }

        NetworkBroadcastUtils::broadcastPackets($this->arena->getPlayers(), [LevelEventPacket::create(
            $eventId,
            $eventData,
            null
        )]);
    }
}