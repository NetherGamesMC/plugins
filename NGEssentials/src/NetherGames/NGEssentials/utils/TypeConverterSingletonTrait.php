<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace NetherGames\NGEssentials\utils;

use pocketmine\network\mcpe\convert\TypeConverter;
use function spl_object_id;

trait TypeConverterSingletonTrait
{
    /** @var self[] */
    private static array $instance = [];

    private static function make(TypeConverter $typeConverter): self
    {
        return new self($typeConverter);
    }

    private function __construct(TypeConverter $typeConverter)
    {
        //NOOP
    }

    public static function getInstance(TypeConverter $typeConverter): self
    {
        return self::$instance[spl_object_id($typeConverter)] ??= self::make($typeConverter);
    }

    /**
     * @return array<int, self>
     */
    public static function getAll(): array
    {
        return self::$instance;
    }

    public static function setInstance(self $instance, TypeConverter $typeConverter): void
    {
        self::$instance[spl_object_id($typeConverter)] = $instance;
    }

    public static function reset(): void
    {
        self::$instance = [];
    }
}