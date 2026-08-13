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

use InvalidArgumentException;
use libforms\elements\Element;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Toggle implements Component
{
    public function asElement(string $text, mixed $value): Element
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException("Value must be of type bool");
        }
        return new \libforms\elements\Toggle(
            text: $text,
            default: $value
        );
    }

    public function processData(mixed $value): bool
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException("Value must be of type bool");
        }
        return $value;
    }
}