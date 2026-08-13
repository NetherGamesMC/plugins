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

use GlobalLogger;
use pocketmine\scheduler\Task;
use RdKafka\Conf;
use RdKafka\Producer;
use Throwable;

/**
 * non-blocking kafka publisher implementation. Provides fast topic event producer for
 * PocketMine-MP. The task exists to pull logs from librdkafka worker.
 */
class KafkaPublisher extends Task
{
    /** @var Producer */
    private Producer $producer;
    /** @var ProducerTopic[] */
    private array $topics = [];

    public function __construct(array $config = [])
    {
        $conf = new Conf();
        foreach ($config as $key => $value) {
            $conf->set($key, $value);
        }

        $conf->set('metadata.broker.list', KafkaUtility::getKafkaEndpoint());
        $conf->setLogCb(KafkaUtility::getKafkaLogger());

        $this->producer = new Producer($conf);
    }

    /**
     * Publish any message into the topic (event) name.
     */
    public function publishMessage(string $topicName, string $message, ?string $key = null, int $partition = RD_KAFKA_PARTITION_UA): void
    {
        $topic = $this->topics[$topicName] ?? $this->producer->newTopic($topicName);

        try {
            $topic->produce($partition, 0, $message, $key);

            if (isset($this->topics[$topicName])) {
                $this->topics[$topicName] = $topic;
            }
        } catch (Throwable $error) {
            GlobalLogger::get()->critical("KafkaPublisher: Cannot publish message into $topicName");
            GlobalLogger::get()->logException($error);

            unset($this->topics[$topicName]);
        }
    }

    /**
     * Callback function to pull any logs from kafka.
     */
    public function onRun(): void
    {
        $this->producer->poll(0);
    }
}