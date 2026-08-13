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

use libforms\elements\Element;

/**
 * A small interface used with components attributes to indicate how to render values.
 */
interface Component
{
    /**
     * Returns a form element to be rendered
     *
     * @param string $text - A label to associate with the element
     * @param mixed $value - The current value of the element
     * @return Element
     */
    public function asElement(string $text, mixed $value): Element;

    /**
     * Used as a way to process incoming form data
     *
     * @param mixed $value - The response value from the form
     * @return mixed - The processed value
     */
    public function processData(mixed $value): mixed;
}