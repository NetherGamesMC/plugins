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

namespace libReplay\session\utils;

use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use function is_bool;
use function zstd_compress;
use function zstd_uncompress;
use const ZSTD_COMPRESS_LEVEL_DEFAULT;

final class ZstdCompressor implements Compressor
{
    use SingletonTrait;

    public const DEFAULT_LEVEL = ZSTD_COMPRESS_LEVEL_DEFAULT;

    /** @var int */
    private int $level;

    public function __construct(int $level = self::DEFAULT_LEVEL)
    {
        $this->level = $level;
    }

    public function getNetworkId(): int
    {
        return CompressionAlgorithm::ZLIB;
    }

    /**
     * @param string $payload
     * @return string
     */
    public function decompress(string $payload): string
    {
        $result = zstd_uncompress($payload);

        if (is_bool($result)) {
            throw new DecompressionException("Failed to decompress data");
        }

        return $result;
    }

    public function compress(string $payload): string
    {
        $result = zstd_compress($payload, $this->level);

        if (is_bool($result)) {
            throw new AssumptionFailedError("ZSTD compression failed");
        }

        return $result;
    }

    public function getCompressionThreshold(): int
    {
        return 0;
    }
}
