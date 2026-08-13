<?php
declare(strict_types=1);

namespace lobby\features\activity;

use Closure;

class ActivityIntent
{
    public function __construct(private string $player, private Closure $onExit)
    {

    }

    public function exit(bool $isDisconnect): void
    {
        $call = $this->onExit;
        $call($isDisconnect);
    }

    /**
     * @return string
     */
    public function getPlayer(): string
    {
        return $this->player;
    }
}