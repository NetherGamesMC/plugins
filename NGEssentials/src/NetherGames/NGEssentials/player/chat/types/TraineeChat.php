<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TraineeChat extends ServerWideChat
{
    public const PREFIX = TextFormat::YELLOW . 'TRAINEE' . TextFormat::RESET . ' » ';

    public function __construct(ChatManager $chatManager)
    {
        parent::__construct(
            'Trainee Chat',
            ChatChannel::CHANNEL_TRAINEE,
            $chatManager,
            '940611835894300723/ZEG6juP5Xz0A7-rkz92fZZiQS4Ae3QUp-yGQwDf4lT_og8Iw9V8LX39RdUYFXy91C3sl'
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        parent::broadcast($player, $message);

        $this->sendEntry($player, $message, 'trainee');
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