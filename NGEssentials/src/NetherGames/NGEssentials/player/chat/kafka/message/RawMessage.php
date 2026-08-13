<?php

namespace NetherGames\NGEssentials\player\chat\kafka\message;

use pocketmine\player\Player;

class RawMessage extends Message
{
    public const KEY_TEXT = "text";

    public function __construct(private string $message)
    {
        parent::__construct(Message::MESSAGE_RAW);
    }

    public static function fromArray(array $array): self
    {
        return new self($array[self::KEY_TEXT]);
    }

    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_TEXT => $this->message
        ];
    }

    public function getMessage(Player $player): string
    {
        return $this->message;
    }
}