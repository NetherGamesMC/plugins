<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
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

namespace libminigames\settings\components;

use Attribute;
use InvalidArgumentException;
use libforms\elements\Element;

/**
 * TODO: Given that libforms doesn't support floats in sliders, these have to be limited to ints. When the library supports floats, this can be changed.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Slider implements Component
{
    public function __construct(
        public int $min,
        public int $max,
        public int $step = 1
    )
    {
        if ($min > $max) {
            throw new InvalidArgumentException("Min value cannot be greater than max value");
        }
        if ($step <= 0) {
            throw new InvalidArgumentException("Step value cannot be less than or equal to 0");
        }
    }

    public function asElement(string $text, mixed $value): Element
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException("Value must be an integer");
        }
        $slider = new \libforms\elements\Slider($text, $this->min, $this->max);
        $slider->setStep($this->step);
        $slider->setDefault($value);
        return $slider;
    }

    public function processData(mixed $value): int
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException("Value must be an integer");
        }
        if ($value < $this->min || $value > $this->max) {
            throw new InvalidArgumentException("Value must be between min and max");
        }
        return $value;
    }
}