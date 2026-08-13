<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\RankedChannel;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class RankedChat extends ServerWideChat
{
    public const PREFIX = TextFormat::LIGHT_PURPLE . 'RANKED' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Ranked Chat',
            ChatChannel::CHANNEL_RANKED,
            $chatManager
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'ranked');
    }

    protected function getKey(Player $player): string
    {
        /** @var RankedChannel $channel */
        $channel = $this->getChatChannel();

        return $channel->getKey();
    }

    public function getPrefix(): string
    {
        return self::PREFIX;
    }
}