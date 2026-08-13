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
use function is_numeric;

class Dropdown extends Element
{
    /**
     * @param string[] $options
     */
    public function __construct(string $text, private array $options, private int $default = -1, ?Closure $callable = null, bool $callbackOnDefault = false)
    {
        parent::__construct($text, $callable, $callbackOnDefault);
    }

    public function getData(Player $player): array
    {
        $data = parent::getData($player);

        $data['type'] = 'dropdown';
        $data['options'] = $this->getOptions();

        if (($default = $this->getDefault()) !== 0) {
            $data['default'] = $default;
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getDefault(): int
    {
        return $this->default;
    }

    public function setDefault(int $default): void
    {
        $this->default = $default;
    }

    /**
     * @param Player $player
     * @param mixed $data
     */
    public function runCallable(Player $player, $data): bool
    {
        if (is_numeric($data) && isset($this->getOptions()[(int)$data])) {
            return parent::runCallable($player, (int)$data);
        }

        if ($data !== -1 && !empty($this->getOptions())) {
            throw new PacketHandlingException($player->getName() . ' tried to send wrong form data: ' . $data);
        }

        return false;
    }
}