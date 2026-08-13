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
use RdKafka\KafkaConsumer as KafkaSubscriber;
use RdKafka\Producer;

class KafkaUtility
{
    public const EVENT_SEVERITY_EMERGENCY = 0;

    // Event severity, see https://github.com/edenhill/librdkafka/blob/master/src-cpp/rdkafkacpp.h
    public const EVENT_SEVERITY_ALERT = 1;
    public const EVENT_SEVERITY_CRITICAL = 2;
    public const EVENT_SEVERITY_ERROR = 3;
    public const EVENT_SEVERITY_WARNING = 4;
    public const EVENT_SEVERITY_NOTICE = 5;
    public const EVENT_SEVERITY_INFO = 6;
    public const EVENT_SEVERITY_DEBUG = 7;
    /** @var string */
    private static string $kafkaEndpoint;

    /**
     * @return Closure The callback function for logging utility.
     */
    public static function getKafkaLogger(): Closure
    {
        return static function (KafkaSubscriber|Producer $kafka, int $level, string $facility, string $message): void {
            switch ($level) {
                case KafkaUtility::EVENT_SEVERITY_EMERGENCY:
                    GlobalLogger::get()->emergency($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_ALERT:
                    GlobalLogger::get()->alert($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_CRITICAL:
                    GlobalLogger::get()->critical($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_ERROR:
                    GlobalLogger::get()->error($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_WARNING:
                    GlobalLogger::get()->warning($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_NOTICE:
                    GlobalLogger::get()->notice($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_INFO:
                    GlobalLogger::get()->info($message);
                    break;
                case KafkaUtility::EVENT_SEVERITY_DEBUG:
                    GlobalLogger::get()->debug($message);
                    break;
                default:
                    GlobalLogger::get()->error("Error: log callback for $level does not exists? New librdkafka?");
                    break;
            }
        };
    }

    public static function getKafkaEndpoint(): string
    {
        return self::$kafkaEndpoint;
    }

    public static function setKafkaEndpoint(string $kafkaEndpoint): void
    {
        self::$kafkaEndpoint = $kafkaEndpoint;
    }
}