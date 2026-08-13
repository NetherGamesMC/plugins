<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GuildChat extends ServerWideChat
{
    public const PREFIX = TextFormat::DARK_GREEN . 'GUILD' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Guild Chat',
            ChatChannel::CHANNEL_GUILD,
            $chatManager
        );
    }

    public function canBeUsed(Player $player): bool
    {
        $playerData = $this->getChatManager()->getPlugin()->getPlayerData();
        return parent::canBeUsed($player) && $playerData->getInt($player, PlayerData::GUILD) > 0;
    }

    public function broadcast(Player $player, string $message): void
    {
        $playerData = $this->getChatManager()->getPlugin()->getPlayerData();

        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'guild', [
            'guild_id' => $playerData->getInt($player, PlayerData::GUILD),
        ]);
    }

    public function getPrefix(): string
    {
        return self::PREFIX;
    }

    protected function getKey(Player $player): string
    {
        /** @var GuildChannel $channel */
        $channel = $this->getChatChannel();
        $playerData = $this->getChatManager()->getPlugin()->getPlayerData();

        return $channel->getKey($playerData->getInt($player, PlayerData::GUILD));
    }
}