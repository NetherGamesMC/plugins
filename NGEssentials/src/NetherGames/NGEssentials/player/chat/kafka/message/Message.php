<?php

namespace NetherGames\NGEssentials\player\chat\kafka\message;

use InvalidArgumentException;
use pocketmine\player\Player;

abstract class Message
{
    public const MESSAGE_RAW = 0;
    public const MESSAGE_TRANSLATED = 1;

    public const KEY_TYPE = "type";

    public function __construct(private int $messageType)
    {
    }

    public static function fromArray(array $array): self
    {
        return match ($array[self::KEY_TYPE]) {
            self::MESSAGE_RAW => RawMessage::fromArray($array),
            self::MESSAGE_TRANSLATED => TranslatedMessage::fromArray($array),
            default => throw new InvalidArgumentException("Unknown message type: " . $array[self::KEY_TYPE]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getArray(): array
    {
        return [
            self::KEY_TYPE => $this->messageType
        ];
    }

    abstract public function getMessage(Player $player): string;
}