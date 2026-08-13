<?php

declare(strict_types=1);

namespace libVanilla\block;

use libVanilla\block\blockscheduler\BlockScheduler;
use libVanilla\block\blockscheduler\LoadBalancingBlockScheduler;
use pocketmine\scheduler\TaskScheduler;

final class HopperConfig
{

    /** @var HopperConfig|null */
    private static ?HopperConfig $instance = null;
    /** @var BlockScheduler */
    private BlockScheduler $blockScheduler;

    public function __construct(TaskScheduler $scheduler)
    {
        $this->blockScheduler = new LoadBalancingBlockScheduler($scheduler, 100);
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public static function setInstance(HopperConfig $instance): void
    {
        if (self::$instance !== $instance && self::hasInstance()) {
            self::$instance->destroy();
        }
        self::$instance = $instance;
    }

    public static function hasInstance(): bool
    {
        return self::$instance !== null;
    }

    private function destroy(): void
    {
        $this->blockScheduler->destroy();
    }

    public function getTransferTickRate(): int
    {
        return 8;
    }

    public function getTransferPerTick(): int
    {
        return 1;
    }

    public function getItemSuckingTickRate(): int
    {
        return 1;
    }

    public function getItemSuckingPerTick(): int
    {
        return 16;
    }

    public function getBlockScheduler(): BlockScheduler
    {
        return $this->blockScheduler;
    }
}