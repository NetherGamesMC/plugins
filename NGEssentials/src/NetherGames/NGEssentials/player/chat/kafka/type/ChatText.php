<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\player\chat\kafka\message\Message;

class ChatText extends SingleMessageText
{
    public function __construct(Message $message)
    {
        parent::__construct(self::TYPE_CHAT, $message);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Message::fromArray($data[self::KEY_MESSAGE])
        );
    }

    /**
     * @inheritDoc
     */
    public function handle(array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $recipient->sendMessage($this->message->getMessage($recipient));
        }
    }
}