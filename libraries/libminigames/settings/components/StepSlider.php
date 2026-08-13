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
use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StepSlider implements Component
{
    /**
     * @param array<int, string> $values
     */
    public function __construct(protected array $values)
    {
        foreach ($values as $key => $value) {
            if (!is_int($key)) {
                throw new InvalidArgumentException("Keys of the values array must be integers");
            }

            if (!is_string($value)) {
                throw new InvalidArgumentException("Values array must be strings");
            }
        }
    }

    public function asElement(string $text, mixed $value): Element
    {
        $key = array_search($value, $this->values, true);
        if ($key === false) {
            throw new InvalidArgumentException("Value '" . var_export($value, true) . "' is not in the list of values");
        }
        if (!is_int($key)) {
            throw new InvalidArgumentException("String keys are not supported in dropdown elements.");
        }

        return new \libforms\elements\StepSlider(
            text: $text,
            steps: $this->values,
            default: $key
        );
    }

    public function processData(mixed $value): mixed
    {
        if (!isset($this->values[$value])) {
            throw new InvalidArgumentException("Unable to locate value by key " . var_export($value, true));
        }
        return $this->values[$value];
    }
}