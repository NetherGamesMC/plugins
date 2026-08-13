<?php

namespace NetherGames\NGEssentials\kafka;

use Closure;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Server;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Utils;
use RdKafka\Message;
use function array_pop;
use function explode;
use function implode;

class KafkaServerTopic
{
    private int $id = 0;

    public const TIMEOUT = 5; // seconds

    /** @var ?KafkaPublisher */
    private ?KafkaPublisher $publisher;
    /** @var array<int, Closure> */
    private array $messages = [];
    /** @var array<int, array<int, ?Closure>> */
    private array $timers = [];
    private string $serverUniqueId;

    public function __construct(private string $topic, ServerManager $serverManager, Closure $onMessage)
    {
        Utils::validateCallableSignature(function (string $key, string $message): void {}, $onMessage);

        $plugin = $serverManager->getPlugin();
        $region = $serverManager->getServerRegion();

        $this->publisher = $plugin->getPublisher();

        $pingTopic = $region . "_" . $topic;
        $pongTopic = $pingTopic . "_pong";
        $consumer = $plugin->getConsumer();
        $this->serverUniqueId = $serverManager->getServerUniqueId();

        $consumer?->addTopic($pingTopic, function (Message $message) use ($topic, $onMessage): void {
            $keys = explode(":", $message->key);

            if (array_pop($keys) !== $this->serverUniqueId) return; // ignore message if server unique id is not the same

            $responseServerUniqueId = array_pop($keys);
            $region = explode('-', $responseServerUniqueId)[0];

            $id = array_pop($keys);
            $this->publisher->publishMessage($region . '_' . $topic . '_pong', (string)$id, $responseServerUniqueId);

            $onMessage(implode(":", $keys), $message->payload);
        });

        $consumer?->addTopic($pongTopic, function (Message $message) use ($plugin): void {
            if ($message->key !== $this->serverUniqueId) return; // ignore message if server unique id is not the same

            $id = (int)$message->payload;
            if (isset($this->messages[$id])) {
                $this->messages[$id]();
                unset($this->messages[$id]);
            } else {
                $plugin->getLogger()->debug("KafkaSinglePongReceiver: Received pong message with id $id but no callable was found");
            }
        });

        $plugin->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function (): void {
            $currentTime = time();

            foreach ($this->timers as $time => $timeouts) {
                if ($currentTime - $time >= self::TIMEOUT) {
                    foreach ($timeouts as $id => $timeout) {
                        if ($timeout !== null) {
                            $timeout();
                        }

                        unset($this->messages[$id]);
                    }

                    unset($this->timers[$time]);
                }
            }
        }), 20, 20);
    }

    public function send(Server $server, string $key, string $message, Closure $onPong, ?Closure $onTimeout = null): void
    {
        $id = $this->id++;

        $this->messages[$id] = $onPong;
        $this->timers[time()][$id] = $onTimeout;

        $this->publisher?->publishMessage($server->getRegion() . '_' . $this->topic, $message, $key . ":" . $id . ":" . $this->serverUniqueId . ':' . $server->getUniqueId());
    }
}