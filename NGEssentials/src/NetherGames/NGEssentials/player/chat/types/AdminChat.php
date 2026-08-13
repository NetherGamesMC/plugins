<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AdminChat extends ServerWideChat
{
    public const PREFIX = TextFormat::RED . 'ADMIN' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Admin Chat',
            ChatChannel::CHANNEL_ADMIN,
            $chatManager,
            '940612301734694942/rtkwijLeVC_h2tgtuLlgp4Zx3pdIix-10mvMoe3PhAeYL2IAJdJkMD2ZkPiyhF_ni1F6',
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'admin');
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