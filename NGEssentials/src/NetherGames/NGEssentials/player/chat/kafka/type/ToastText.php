<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\player\chat\kafka\message\Message;
use pocketmine\player\Player;

class ToastText extends TextType
{
    public const KEY_TITLE = "title";
    public const KEY_BODY = "body";

    public function __construct(
        private readonly Message $title,
        private readonly Message $body,
    )
    {
        parent::__construct(self::TYPE_TOAST);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Message::fromArray($data[self::KEY_TITLE]),
            Message::fromArray($data[self::KEY_BODY]),
        );
    }

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_TITLE => $this->title,
            self::KEY_BODY => $this->body,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $recipient->sendToastNotification($this->title->getMessage($recipient), $this->body->getMessage($recipient));
        }
    }
}