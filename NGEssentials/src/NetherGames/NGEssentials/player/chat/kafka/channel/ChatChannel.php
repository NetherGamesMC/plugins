<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use pocketmine\player\Player;
use function strlen;

abstract class ChatChannel
{
    public const CHANNEL_GLOBAL = 0;
    public const CHANNEL_GUILD = 1;
    // 2
    public const CHANNEL_REPORTS = 3;
    public const CHANNEL_RANKED = 4;
    public const CHANNEL_STAFF = 5;
    public const CHANNEL_TRAINEE = 6;
    public const CHANNEL_MODERATION = 7;
    public const CHANNEL_ADMIN = 8;
    public const CHANNEL_PRIVATE = 9;
    public const CHANNEL_SERVER_TYPE = 10;

    public function __construct(private int $channelId)
    {

    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    /**
     * @param string $key
     * @return array{0: int, 1: string} [channelId, channelKey]
     */
    public static function getKeys(string $key): array
    {
        $firstColon = strpos($key, ":");

        if ($firstColon === false) {
            return [(int)$key, ""];
        }

        return [
            (int)substr($key, 0, $firstColon),
            substr($key, $firstColon + 1)
        ];
    }

    public function canBeUsed(Player $player): bool
    {
        return true;
    }

    /**
     * @return Player[]
     */
    abstract public function getRecipients(string $channelKey): array;

    protected function getGlobalKey(string $channelKey): string
    {
        return $this->channelId . (strlen($channelKey) > 0 ? ":" . $channelKey : "");
    }
}