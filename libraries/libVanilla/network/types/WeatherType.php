<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\network\types;

use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\utils\EnumTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static WeatherType CLEAR()
 * @method static WeatherType RAIN()
 * @method static WeatherType RAINY_THUNDERSTORM()
 * @method static WeatherType THUNDERSTORM()
 */
class WeatherType
{
    use EnumTrait {
        __construct as private Enum__construct;
    }

    private function __construct(string $name, protected int $eventMask)
    {
        $this->Enum__construct($name);
    }

    protected static function setup(): void
    {
        self::register(new WeatherType("clear", 0b00));
        self::register(new WeatherType("rain", 0b01));
        self::register(new WeatherType("thunderstorm", 0b10));
        self::register(new WeatherType("rainy_thunderstorm", 0b11));
    }

    public static function fromString(string $name): ?WeatherType
    {
        /** @var WeatherType[] $types */
        $types = self::_registryGetAll();
        foreach ($types as $type) {
            if (strtolower($type->name()) === strtolower($name)) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Returns a list of level events to send to the client to change the weather.
     *
     * @return array{int, int} List of level events to send to the client
     */
    public function encode(): array
    {
        return [
            $this->isRainy() ? LevelEvent::START_RAIN : LevelEvent::STOP_RAIN,
            $this->isThunderstorm() ? LevelEvent::START_THUNDER : LevelEvent::STOP_THUNDER
        ];
    }

    public function isRainy(): bool
    {
        return ($this->eventMask & 0b01) !== 0;
    }

    public function isThunderstorm(): bool
    {
        return ($this->eventMask & 0b10) !== 0;
    }
}