<?php
/*
 *
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy, larryTheCoder
 */

namespace NetherGames\NGEssentials\kafka;

use Closure;
use GlobalLogger;
use pmmp\thread\ThreadSafeArray;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperHandler;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use RdKafka\Conf;
use RdKafka\KafkaConsumer as KafkaSubscriber;
use Throwable;

/**
 * Kafka consumer implementation in PHP, this basic implementation with string key-value pairs provides
 * asynchronous consumer (listener) system which has the functionality to:
 * - Listen on events (topics) and the message will be passed to a callback
 * - Removing events (topics).
 *
 * The configuration properties can be found here: https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
 *
 * To successfully run a consumer, a "metadata.broker.list" — the list of kafka brokers must be configured in order for
 * topics to be captured. The broker list can be defined as "localhost:31300,localhost:31302" where "," is the domains
 * separator. Only string are allowed, meaning that Closure is certainly not allowed in this configuration properties.
 */
class KafkaConsumer extends Thread
{
    public const KAFKA_ADD_TOPIC = 0;
    public const KAFKA_REMOVE_TOPIC = 1;

    /** @var Closure[] */
    private static array $topicsConsumers = [];
    /** @var string */
    private string $config;

    /** @var ThreadSafeArray */
    public ThreadSafeArray $inboundQueue;
    /** @var ThreadSafeArray */
    public ThreadSafeArray $outboundQueue;

    /** @var SleeperHandlerEntry */
    private SleeperHandlerEntry $sleeperEntry;
    /** @var bool */
    private bool $cleanShutdown = true;

    /**
     * @param SleeperHandler $eventLoop
     * @param string $composerPath
     * @param ThreadSafeLogger $logger
     * @param string[] $configurations
     */
    public function __construct(
        SleeperHandler           $eventLoop,
        private string           $composerPath,
        private ThreadSafeLogger $logger,
        array                    $configurations = [],
    )
    {
        $this->config = igbinary_serialize(['metadata.broker.list' => KafkaUtility::getKafkaEndpoint()] + $configurations);
        $this->inboundQueue = new ThreadSafeArray();
        $this->outboundQueue = new ThreadSafeArray();

        $channelReader = new PthreadsChannelReader($this->outboundQueue);

        $this->sleeperEntry = $eventLoop->addNotifier(function () use ($channelReader): void {
            while (($raw = $channelReader->read()) != null) {
                /** @var Message $message */
                $message = igbinary_unserialize($raw);

                if (!isset(self::$topicsConsumers[$message->topic_name])) {
                    GlobalLogger::get()->warning("Topic {$message->topic_name} is not found in the list, ignoring message.");
                    continue;
                }

                try {
                    (self::$topicsConsumers[$message->topic_name])($message);
                } catch (Throwable $error) {
                    GlobalLogger::get()->error("Consumer topic for {$message->topic_name} has thrown an exception.");
                    GlobalLogger::get()->logException($error);
                }
            }
        });
    }

    /**
     * @return void
     */
    public function shutdownHandler(): void
    {
        if ($this->cleanShutdown) {
            $this->logger->info('Kafka Thread: Graceful shutdown complete');
        } else {
            $error = error_get_last();

            if ($error === null) {
                $this->logger->emergency('Kafka consumer shutdown unexpectedly');
            } else {
                $this->logger->emergency('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']);
            }
        }
    }

    /**
     * @param string $topic      The topic to listen into.
     * @param Closure $onMessage The callback to forward the data to.
     *
     * @phpstan-param Closure(Message): void $onMessage
     */
    public function addTopic(string $topic, Closure $onMessage): void
    {
        $this->synchronized(function () use ($topic, $onMessage): void {
            $this->inboundQueue[] = igbinary_serialize([self::KAFKA_ADD_TOPIC, [$topic]]);

            self::$topicsConsumers = array_merge(self::$topicsConsumers, [$topic => $onMessage]);

            $this->notify();
        });
    }

    /**
     * @param Closure[] $topics The list of topics to listen into
     *
     * @phpstan-param array<string, Closure(Message): void> $topics
     */
    public function addAllTopic(array $topics): void
    {
        $this->synchronized(function () use ($topics): void {
            $this->inboundQueue[] = igbinary_serialize([self::KAFKA_ADD_TOPIC, array_keys($topics)]);

            self::$topicsConsumers = array_merge(self::$topicsConsumers, $topics);

            $this->notify();
        });
    }

    public function removeTopic(string $topic): void
    {
        if (!isset(self::$topicsConsumers[$topic])) {
            return;
        }

        $this->synchronized(function () use ($topic): void {
            $this->inboundQueue[] = igbinary_serialize([self::KAFKA_REMOVE_TOPIC, $topic]);

            unset(self::$topicsConsumers[$topic]);

            $this->notify();
        });
    }

    public function getActiveConsumers(): array
    {
        return array_keys(self::$topicsConsumers);
    }

    /**
     * @throws Exception
     */
    protected function onRun(): void
    {
        register_shutdown_function([$this, 'shutdownHandler']);

        GlobalLogger::set($this->logger);

        try {
            if (!empty($this->composerPath)) {
                require $this->composerPath;
            }

            /** @var string[] $config */
            $config = igbinary_unserialize($this->config) + ['auto.offset.reset' => 'latest', 'check.crcs' => true];

            $conf = new Conf();
            foreach ($config as $key => $value) {
                $conf->set($key, $value);
            }

            $conf->setLogCb(KafkaUtility::getKafkaLogger());

            $channelReader = new PthreadsChannelReader($this->inboundQueue);
            $channelWriter = new SnoozeAwarePthreadsChannelWriter($this->outboundQueue, $this->sleeperEntry->createNotifier());

            $kafka = new KafkaSubscriber($conf);

            while (!$this->isKilled) {
                $subscribed = $kafka->getSubscription();
                $changed = false;

                while (($raw = $channelReader->read()) != null) {
                    [$key, $message] = igbinary_unserialize($raw);

                    switch ($key) {
                        case self::KAFKA_ADD_TOPIC:
                            $subscribed = array_merge($subscribed, $message);
                            $changed = true;
                            break;
                        case self::KAFKA_REMOVE_TOPIC:
                            if (($index = array_search($message, $subscribed)) !== false) {
                                unset($subscribed[$index]);
                                $subscribed = array_values($subscribed);
                            }
                            $changed = true;
                            break;
                    }
                }

                if ($changed) {
                    $kafka->subscribe($subscribed);
                }

                $message = $kafka->consume(1000);
                if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                    $channelWriter->write(igbinary_serialize($message));
                }
            }
        } catch (Throwable $error) {
            $this->logger->critical("Kafka Consumer thread has thrown an exception.");
            $this->logger->logException($error);

            $this->cleanShutdown = false;
        } finally {
            if (!$this->isKilled) {
                $this->isKilled = true;
            }

            if (isset($kafka)) {
                $kafka->close();
            }
        }
    }
}