<?php

namespace NetherGames\NGEssentials\elasticsearch;

use NetherGames\NGEssentials\elasticsearch\entry\IndexEntry;
use NetherGames\NGEssentials\ServerManager;
use pmmp\thread\Thread as NativeThread;
use pocketmine\thread\log\ThreadSafeLoggerAttachment;
use pocketmine\thread\Thread;
use pocketmine\thread\Worker;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use function preg_filter;

class ElasticSearchLoggerAttachment extends ThreadSafeLoggerAttachment
{
    public function __construct(private ElasticSearch $elasticSearch)
    {
    }

    public function log(string $level, string $message): void
    {
        $thread = NativeThread::getCurrentThread();
        if ($thread === null) {
            $threadName = "Server thread";
        } elseif ($thread instanceof Thread or $thread instanceof Worker) {
            $threadName = $thread->getThreadName() . " thread";
        } else {
            $threadName = (new ReflectionClass($thread))->getShortName() . " thread";
        }

        $this->elasticSearch->addEntry(new IndexEntry(
            index: 'server_logs',
            data: [
                'content' => preg_filter('/^\[(.*?)] /', '', TextFormat::clean($message)),
                'server_id' => ServerManager::getServerUniqueId(),
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => $level,
                'thread' => $threadName,
            ],
        ));
    }
}