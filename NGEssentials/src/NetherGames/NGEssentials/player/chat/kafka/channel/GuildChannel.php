<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;
use function array_filter;

class GuildChannel extends ChatChannel
{
    public function __construct(private readonly NGEssentials $plugin)
    {
        parent::__construct(self::CHANNEL_GUILD);
    }

    public function getKey(int $guildId): string
    {
        return parent::getGlobalKey((string)$guildId);
    }

    public function canBeUsed(Player $player): bool
    {
        return parent::canBeUsed($player) && $this->plugin->getPlayerData()->getBool($player, PlayerData::GUILD_CHAT);
    }

    public function getRecipients(string $channelKey): array
    {
        $guildManager = $this->plugin->getPlayerManager()->getSocialManager()->getGuildsManager();

        return array_filter($guildManager->getGuild((int)$channelKey)?->getLocalOnlinePlayers() ?? [], fn(Player $player) => $this->canBeUsed($player));
    }
}