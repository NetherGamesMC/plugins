<?php

namespace NetherGames\NGEssentials\player\cosmetics\types;

use function md5;
use function serialize;

readonly class CosmeticDataEntry
{
    private string $hash;

    /**
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function __construct(
        public int   $id,
        public array $data
    )
    {
        $this->hash = md5(serialize($this->data));
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}