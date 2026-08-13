<?php

namespace meltdown\utils\math;

use meltdown\utils\entity\PowerupEntity;

class ValueWeightWrapper
{
    /**
     * @phpstan-param class-string<PowerupEntity> $value
     */
    public function __construct(
        public string $value,
        public float $weight
    )
    {
    }
}