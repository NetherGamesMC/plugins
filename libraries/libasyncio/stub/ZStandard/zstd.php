<?php

const LIB_ZSTD_VERSION_STRING = '0.8.0';
const LIB_ZSTD_VERSION_NUMBER = 8.0;

const ZSTD_COMPRESS_LEVEL_MIN = 0;
const ZSTD_COMPRESS_LEVEL_DEFAULT = 3;
const ZSTD_COMPRESS_LEVEL_MAX = 22;

/**
 * Compress via Facebook\Zstd.
 *
 * Level 0-22, 0 for no compression.
 * 22 is max compression.
 * You can use ZSTD_COMPRESS_LEVEL_MAX,
 * ZSTD_COMPRESS_LEVEL_MIN, and
 * ZSTD_COMPRESS_LEVEL_DEFAULT for the
 * level argument.
 *
 * @param string $data
 * @param int $level
 *
 * @return string|bool
 */
function zstd_compress(string $data, int $level = 3)
{
    if (empty($data)) {
        return false;
    }

    return '';
}

/**
 * Uncompress/Decompress via Facebook\Zstd.
 *
 * @param string $data
 *
 * @return string|bool
 */
function zstd_uncompress(string $data)
{
    if (empty($data)) {
        return false;
    }

    return '';
}
