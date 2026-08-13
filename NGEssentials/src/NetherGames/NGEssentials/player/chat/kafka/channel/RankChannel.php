<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use pocketmine\player\Player;
use pocketmine\Server;
use function array_filter;

class RankChannel extends ChatChannel
{
    /**
     * @param string[] $permissions
     */
    public function __construct(int $channelId, private readonly Server $server, private readonly array $permissions)
    {
        parent::__construct($channelId);
    }

    public function getKey(): string
    {
        return parent::getGlobalKey("");
    }

    /**
     * @return Player[]
     */
    public function getRecipients(string $channelKey): array
    {
        return array_filter($this->server->getOnlinePlayers(), fn(Player $player) => $this->canBeUsed($player));
    }

    public function canBeUsed(Player $player): bool
    {
        return array_reduce($this->permissions, fn(bool $carry, string $permission) => $carry || $player->hasPermission($permission), false);
    }
}