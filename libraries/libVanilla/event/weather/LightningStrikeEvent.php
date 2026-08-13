<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\event\weather;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\world\Position;
use pocketmine\world\World;

class LightningStrikeEvent extends WeatherEvent implements Cancellable
{
    use CancellableTrait;

    /**
     * @param float $baseDamage The initial damage dealt to entities, possibly increased by fire damage
     * @param bool $priming If true, resulting bolt will try to randomly ignite blocks within a 2x2 radius around struck block
     */
    public function __construct(private World $world, private Position $position, private float $baseDamage, private bool $priming)
    {
        parent::__construct($this->world);
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getBaseDamage(): float
    {
        return $this->baseDamage;
    }

    public function setBaseDamage(float $baseDamage): void
    {
        $this->baseDamage = $baseDamage;
    }

    public function setPriming(bool $priming): void
    {
        $this->priming = $priming;
    }

    public function isPriming(): bool
    {
        return $this->priming;
    }
}