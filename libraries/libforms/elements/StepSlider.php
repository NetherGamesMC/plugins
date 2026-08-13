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
use function json_encode;

class StepSlider extends Element
{
    /**
     * @param string[] $steps
     */
    public function __construct(string $text, private array $steps, private int $default = -1, ?Closure $callable = null, bool $callbackOnDefault = false)
    {
        parent::__construct($text, $callable, $callbackOnDefault);
    }

    public function getData(Player $player): array
    {
        $data = parent::getData($player);

        $data['type'] = 'step_slider';
        $data['steps'] = $this->getSteps();

        if (($default = $this->getDefault()) !== -1) {
            $data['default'] = $default;
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param string[] $steps
     */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
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
        if (is_numeric($data) && isset($this->getSteps()[(int)$data])) {
            return parent::runCallable($player, (int)$data);
        }

        throw new PacketHandlingException($player->getName() . ' tried to send wrong form data: ' . $data . ' for element ' . json_encode($this->getData($player), JSON_THROW_ON_ERROR));
    }
}