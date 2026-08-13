<?php

namespace NetherGames\NGEssentials\elasticsearch;

use libasynCurl\Curl;
use NetherGames\NGEssentials\elasticsearch\entry\Entry;
use NetherGames\NGEssentials\elasticsearch\entry\IndexEntry;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function getenv;
use function implode;
use function json_encode;

class ElasticSearch extends ThreadSafe
{
    public const FLUSH_MAX = 1000;
    public const FLUSH_MIN = 100;

    private ThreadSafeArray $buffer;
    private static ?ElasticSearch $instance = null;

    public function __construct(PluginBase $base)
    {
        $this->buffer = new ThreadSafeArray();
        self::$instance = $this;

        $base->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            if ($this->buffer->count() >= self::FLUSH_MIN) {
                $this->flush();
            }
        }), 20);
    }

    public function close(): void
    {
        $this->flush();
    }

    public static function getInstance(): ElasticSearch
    {
        if (self::$instance === null) {
            throw new RuntimeException("ElasticSearch is not initialized properly in this environment.");
        }

        return self::$instance;
    }

    public static function setInstance(ElasticSearch $instance): void
    {
        if (self::$instance !== null) {
            throw new RuntimeException("ElasticSearch is already initialized.");
        }

        self::$instance = $instance;
    }

    public function addEntry(Entry $entry): void
    {
        if ($this->getURL() === null) {
            return;
        }

        if ($entry instanceof IndexEntry) {
            foreach ($entry->asArray() as $value) {
                $this->buffer[] = json_encode($value) . TextFormat::EOL;
            }
        } else {
            $this->buffer[] = json_encode($entry->asArray()) . TextFormat::EOL;
        }
    }

    private function getNDJSON(array $buffer): string
    {
        return implode('', $buffer);
    }

    private function flush(): void
    {
        if (($url = $this->getURL()) !== null) {
            $auth = getenv('ELASTIC_SEARCH_AUTH');
            if ($auth === false) {
                throw new RuntimeException("ElasticSearch auth is not set.");
            }

            Curl::postRequest($url . '/_bulk', $this->getNDJSON($this->buffer->chunk(self::FLUSH_MAX)), 10, [
                'Content-Type: application/x-ndjson',
                'Authorization: ApiKey ' . $auth
            ]);
        }
    }

    private function getURL(): ?string
    {
        $url = getenv('ELASTIC_SEARCH_URL');
        return $url === false ? null : $url;
    }
}