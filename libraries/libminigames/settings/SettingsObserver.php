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

namespace libminigames\settings;

use Attribute;

/**
 * This attribute is used to observe a property and call a callback when the value of the property changes.
 *
 * This attribute should annotate a method inside the settings class. The method signature should follow three rules:
 *
 * 1. The method should be public.
 * 2. The method should accept two parameters: the old value and the new value.
 * 3. The method should return void.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class SettingsObserver
{
    /**
     * @param string $property - The name of the property to observe
     */
    public function __construct(private string $property)
    {
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}