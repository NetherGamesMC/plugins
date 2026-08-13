<?php
/**
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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils;

use libDiscord\DiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Field;
use libDiscord\message\embed\MessageEmbed;
use LogLevel;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\discord\EmbedColors;
use pmmp\thread\ThreadSafeArray;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLoggerAttachment;
use pocketmine\utils\TextFormat;
use function in_array;
use function str_contains;
use function strtoupper;

class ErrorLogger extends ThreadSafeLoggerAttachment
{
    public const LOGGING_WEBHOOK_ID = '';

    public static ?DiscordChannel $LOGGING_CHANNEL = null;

    /** @var SleeperHandlerEntry */
    private SleeperHandlerEntry $sleeperEntry;
    /** @var ThreadSafeArray */
    private ThreadSafeArray $stream;

    public function __construct()
    {
        $this->stream = new ThreadSafeArray();

        $this->sleeperEntry = Server::getInstance()->getTickSleeper()->addNotifier(function (): void {
            while (($logs = $this->stream->shift()) !== null) {
                [$level, $message] = igbinary_unserialize($logs);

                self::getChannel()?->post(self::createMessageFromLog($level, $message));
            }
        });
    }

    public static function getChannel(): ?DiscordChannel
    {
        // implementation required
        return null;
    }

    public static function createMessageFromLog(string $level, string $message): DiscordMessage
    {
        $color = self::getLogColor($level);
        $message = TextFormat::clean($message);
        $embed = MessageEmbed::rich("ERROR - " . strtoupper($level))
            ->setColor($color)
            ->addField(Field::simple("Server", ServerManager::getServerUniqueId()));
        // Attempt to find error message in message
        /** @noinspection RegExpRedundantEscape */
        preg_match("/\]:\s(.*)/", $message, $fieldMatches);
        $embed->addField(Field::simple("Message", $fieldMatches[1] ?? $message));
        if (preg_match("/(?<=--- Stack trace ---\n)(.*)(?=--- End of exception information ---)/s", $message, $traceMatches)) {
            $fieldCount = 1;
            $currentField = Field::simple("Stack trace", "");
            foreach (explode("\n", $traceMatches[1]) as $line) {
                $output = preg_replace("/(#\d+)(?=\s)/m", "**\$1**", $line);
                if (strlen($currentField->value) + strlen($output) > 1024) {
                    $embed->addField($currentField);
                    $currentField = Field::simple("Stack trace - " . (++$fieldCount), "");
                }
                $currentField->value .= $output . "\n";
            }
            $embed->addField($currentField);
        }
        return DiscordMessage::embed($embed);
    }

    public static function getLogColor(string $level): string
    {
        return match ($level) {
            LogLevel::ALERT => EmbedColors::ALERT,
            LogLevel::EMERGENCY => EmbedColors::EMERGENCY,
            default => EmbedColors::CRITICAL
        };
    }

    public function log(string $level, string $message): void
    {
        foreach ([
                     'Too many packets in batch',
                     'Exceeded rate limit',
                     'SavedDataLoadingException',
                     'Failed to decompress data',
                     'has no previous palette to copy from',
                 ] as $needle) {
            if (str_contains($message, $needle)) {
                return;
            }
        }

        if (in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL], true)) {
            $this->stream[] = igbinary_serialize([$level, $message]);
            $this->sleeperEntry->createNotifier()->wakeupSleeper();
        }
    }
}