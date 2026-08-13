<?php

declare(strict_types=1);


namespace libReplay;


use libReplay\session\record\RecordManager;
use libReplay\session\replay\ReplayManager;
use NetherGames\NGEssentials\NGEssentials;

class Replays
{
    /** @var RecordManager|null */
    private ?RecordManager $recordManager = null;
    /** @var ReplayManager|null */
    private ?ReplayManager $replayManager = null;
    /** @var NGEssentials */
    private NGEssentials $plugin;

    public function __construct(NGEssentials $plugin, bool $recordManager, bool $replayManager)
    {
        if ($recordManager || $replayManager) {
            new S3Provider($plugin);
        }

        if (S3Provider::getStorageCredentials() === null) {
            $plugin->getLogger()->warning("Replays credentials are not set up. Replays will not work.");
        } else {
            if ($recordManager) {
                $this->plugin = $plugin;
                $this->recordManager = new RecordManager($this);
            }

            if ($replayManager) {
                $this->plugin = $plugin;
                $this->replayManager = new ReplayManager($this);
            }
        }
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }

    public function getRecordManager(): ?RecordManager
    {
        return $this->recordManager;
    }

    public function getReplayManager(): ?ReplayManager
    {
        return $this->replayManager;
    }
}