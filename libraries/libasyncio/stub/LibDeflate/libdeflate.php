<?php

declare(strict_types=1);

/**
 * PHPStan stub for the libdeflate extension (pmmp/ext-libdeflate).
 * Not loaded at runtime; only used so static analysis can resolve the
 * extension.
 */

/**
 * Equivalent to zlib_encode($data, ZLIB_ENCODING_RAW, $level).
 * Output is raw DEFLATE, decompressible with core zlib inflate_init(ZLIB_ENCODING_RAW).
 *
 * @param string $data
 * @param int $level
 *
 * @return string
 */
function libdeflate_deflate_compress(string $data, int $level = 6)
{
    return '';
}
