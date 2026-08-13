<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use pocketmine\Server;

class GlobalChannel extends ChatChannel
{
    public function __construct(private readonly Server $server)
    {
        parent::__construct(self::CHANNEL_GLOBAL);
    }

    public function getKey(): string
    {
        return parent::getGlobalKey("");
    }

    public function getRecipients(string $channelKey): array
    {
        return $this->server->getOnlinePlayers();
    }
}