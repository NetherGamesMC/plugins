<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms\elements;

use Closure;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use function is_bool;

class Toggle extends Element
{
    public function __construct(string $text, private bool $default = false, ?Closure $callable = null, bool $callbackOnDefault = false)
    {
        parent::__construct($text, $callable, $callbackOnDefault);
    }

    public function getData(Player $player): array
    {
        $data = parent::getData($player);

        $data['type'] = 'toggle';

        if ($this->getDefault()) {
            $data['default'] = true;
        }

        return $data;
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    /**
     * @param Player $player
     * @param mixed $data
     */
    public function runCallable(Player $player, $data): bool
    {
        if (is_bool($data)) {
            return parent::runCallable($player, $data);
        }

        throw new PacketHandlingException($player->getName() . ' tried to send wrong form data: ' . $data);
    }
}