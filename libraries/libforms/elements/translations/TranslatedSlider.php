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

namespace libforms\elements\translations;

use Closure;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use function in_array;
use function is_numeric;
use function range;

class TranslatedSlider extends TranslatedElement
{
    /** @var int */
    private int $step = -1;
    /** @var int */
    private int $default = -1;

    public function __construct(string $text, array $parameters, private int $min, private int $max, ?Closure $callable = null, bool $callbackOnDefault = false)
    {
        parent::__construct($text, $parameters, $callable, $callbackOnDefault);
    }

    public function getData(Player $player): array
    {
        $data = parent::getData($player);

        $data['type'] = 'slider';
        $data['min'] = $this->getMin();
        $data['max'] = $this->getMax();

        if (($step = $this->getStep()) !== -1) {
            $data['step'] = $step;
        }

        if (($default = $this->getDefault()) !== -1) {
            $data['default'] = $default;
        }

        return $data;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): void
    {
        $this->step = $step;
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
        if (is_numeric($data) && in_array((int)$data, range($this->getMin(), $this->getMax(), ($step = $this->getStep()) === -1 ? 1 : $step), true)) {
            return parent::runCallable($player, (int)$data);
        }

        throw new PacketHandlingException($player->getName() . ' tried to send wrong form data: ' . $data);
    }
}