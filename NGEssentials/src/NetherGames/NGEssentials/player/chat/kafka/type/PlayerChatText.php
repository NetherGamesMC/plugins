<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\player\chat\kafka\message\Message;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;

class PlayerChatText extends SingleMessageText
{
    public const KEY_SENDER_XUID = "senderXuid";

    public function __construct(Message $message, private readonly string $senderXuid, int $type = self::TYPE_PLAYER_CHAT)
    {
        parent::__construct($type, $message);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Message::fromArray($data[self::KEY_MESSAGE]),
            $data[self::KEY_SENDER_XUID]
        );
    }

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_SENDER_XUID => $this->senderXuid
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $packet = TextPacket::raw($this->message->getMessage($recipient));
            $packet->type = TextPacket::TYPE_CHAT;
            $packet->sourceName = "";
            $packet->xboxUserId = $this->senderXuid;

            $recipient->getNetworkSession()->sendDataPacket($packet);
        }
    }
}