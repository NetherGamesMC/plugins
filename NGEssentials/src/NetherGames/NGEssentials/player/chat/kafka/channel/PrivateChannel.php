<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use NetherGames\NGEssentials\player\PlayerManager;
use function array_filter;
use function array_map;
use function explode;

class PrivateChannel extends ChatChannel
{
    public function __construct(private readonly PlayerManager $playerManager)
    {
        parent::__construct(self::CHANNEL_PRIVATE);
    }

    /**
     * @param string[] $receivers
     */
    public function getKey(array $receivers): string
    {
        return parent::getGlobalKey(implode(":", $receivers));
    }

    /**
     * @return Player[]
     */
    public function getRecipients(string $channelKey): array
    {
        return array_filter(array_map(
            fn(string $receiverIdentifier) => $this->playerManager->getPlayerFromIdentifier($receiverIdentifier),
            explode(":", $channelKey)
        ));
    }
}