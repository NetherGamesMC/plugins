<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StaffChat extends ServerWideChat
{
    public const PREFIX = TextFormat::GREEN . 'STAFF' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Staff Chat',
            ChatChannel::CHANNEL_STAFF,
            $chatManager,
            '940612133224325120/Sg6GqxXz9JAoGlAScuwfTW14a4AlbOxGUydBFmBWN6_oaNImbImD6l8I4334NwpomVJ9'
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'staff');
    }

    public function getPrefix(): string
    {
        return self::PREFIX;
    }

    protected function getKey(Player $player): string
    {
        /** @var StaffChannel $channel */
        $channel = $this->getChatChannel();

        return $channel->getKey();
    }
}