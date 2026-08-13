<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use NetherGames\NGEssentials\NGEssentials;

class ServerTypeChannel extends ChatChannel
{
    public function __construct(private readonly NGEssentials $plugin)
    {
        parent::__construct(self::CHANNEL_SERVER_TYPE);
    }

    public function getKey(string $serverType): string
    {
        return parent::getGlobalKey($serverType);
    }

    public function getRecipients(string $channelKey): array
    {
        return $channelKey === ($plugin = $this->plugin)->getServerManager()->getServerType() ? $plugin->getServer()->getOnlinePlayers() : [];
    }
}