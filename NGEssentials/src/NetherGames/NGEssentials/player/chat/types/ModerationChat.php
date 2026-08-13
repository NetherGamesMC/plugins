<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ModerationChat extends ServerWideChat
{
    public const PREFIX = TextFormat::BLUE . 'MOD' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Moderation Chat',
            ChatChannel::CHANNEL_MODERATION,
            $chatManager,
            '940612301734694942/rtkwijLeVC_h2tgtuLlgp4Zx3pdIix-10mvMoe3PhAeYL2IAJdJkMD2ZkPiyhF_ni1F6'
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'moderation');
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