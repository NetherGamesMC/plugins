<?php

namespace meltdown\utils\math;

use RuntimeException;

abstract class WeightedDistributionPicker
{
    /**
     * @param ValueWeightWrapper[] $valueWeightWrappers
     * @return mixed
     */
    public static function pickWeighted(array $valueWeightWrappers): mixed
    {
        $threshold = rand() / getrandmax();
        foreach ($valueWeightWrappers as $wrapper) {
            if ($threshold <= $wrapper->weight) {
                return $wrapper->value;
            }
            $threshold -= $wrapper->weight;
        }

        // this is caused by an invalid argument but is thrown when we notice we're in an invalid state
        throw new RuntimeException("WeightedDistributionPicker::pickWeighted() did not pick anything");
    }
}