<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\message\Message;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;

class PlayerWhisperText extends PlayerChatText
{
    public const KEY_SENDER_NAME = "senderName";

    public function __construct(Message $message, string $senderXuid, private readonly string $senderName)
    {
        parent::__construct($message, $senderXuid, self::TYPE_PLAYER_WHISPER);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Message::fromArray($data[self::KEY_MESSAGE]),
            $data[self::KEY_SENDER_XUID],
            $data[self::KEY_SENDER_NAME]
        );
    }

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_SENDER_NAME => $this->senderName
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(array $recipients): void
    {
        parent::handle($recipients);

        $playerData = NGEssentials::getInstance()->getPlayerData();
        foreach ($recipients as $recipient) {
            $playerData->setValue($recipient, PlayerData::REPLY_PLAYER, $this->senderName);
        }
    }
}