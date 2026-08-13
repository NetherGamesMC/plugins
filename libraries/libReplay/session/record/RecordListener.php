<?php

declare(strict_types=1);


namespace libReplay\session\record;


use pocketmine\event\Listener;
use pocketmine\event\server\LowMemoryEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\Server;
use function number_format;
use function round;

class RecordListener implements Listener
{
    /** @var RecordManager */
    private RecordManager $manager;

    public function __construct(RecordManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param ChunkLoadEvent $event
     *
     * @priority MONITOR
     */
    public function onChunkLoad(ChunkLoadEvent $event): void
    {
        if (($recording = $this->getManager()->getRecording($world = $event->getWorld())) !== null) {
            $world->registerChunkListener($recording->getCamera(), $event->getChunkX(), $event->getChunkZ());
        }
    }

    /**
     * @return RecordManager
     */
    private function getManager(): RecordManager
    {
        return $this->manager;
    }

    public function onLowMemory(LowMemoryEvent $event): void
    {
        $worldManager = Server::getInstance()->getWorldManager();

        $memoryUsage = number_format(round(($event->getMemory() / 1024) / 1024, 2), 2);
        $memoryLimit = number_format(round(($event->getMemoryLimit() / 1024) / 1024, 2), 2);
        $logger = $this->getManager()->getManager()->getPlugin()->getLogger();

        foreach ($this->getManager()->getRecordings() as $worldId => $recording) {
            $world = $worldManager->getWorld($worldId);

            if ($world !== null) {
                $logger->info("Stopping recording of world " . $world->getDisplayName() . " due to low memory ({$memoryUsage}MB / {$memoryLimit}MB)");
                $this->getManager()->stopRecording($world, false);
            }
        }
    }
}