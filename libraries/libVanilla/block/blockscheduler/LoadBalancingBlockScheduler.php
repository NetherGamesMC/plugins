<?php

declare(strict_types=1);

namespace libVanilla\block\blockscheduler;

use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\World;

final class LoadBalancingBlockScheduler implements BlockScheduler
{
    /** @var TaskHandler<ClosureTask> */
    private TaskHandler $handler;
    /** @var int */
    private int $capacity;
    /** @var int */
    private int $tick = 0;
    /** @var int */
    private int $position = 0;
    /** @var int[] */
    private array $updateCounts = [];

    public function __construct(TaskScheduler $scheduler, int $capacity)
    {
        $this->capacity = $capacity;
        $this->handler = $scheduler->scheduleRepeatingTask(new ClosureTask(function (): void {
            unset($this->updateCounts[$this->tick++]);
            if (!isset($this->updateCounts[$this->tick])) {
                $this->updateCounts[$this->tick] = 0;
            }
        }), 1);
    }

    public function scheduleDelayedBlockUpdate(World $world, Vector3 $pos, int $min_delay): int
    {
        if (!isset($this->updateCounts[$this->position])) {
            $this->position = $this->tick;
            assert(isset($this->updateCounts[$this->position]));
        }

        if ($this->updateCounts[$this->position] > $this->capacity) {
            $this->updateCounts[++$this->position] = 0;
        }

        $delay_offset = $this->position - $this->tick;
        $delay = $min_delay + $delay_offset;
        ++$this->updateCounts[$this->position];
        $world->scheduleDelayedBlockUpdate($pos, $delay);
        return $delay;
    }

    public function destroy(): void
    {
        if (!$this->handler->isCancelled()) {
            $this->handler->cancel();
        }
    }
}
