<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use InvalidArgumentException;
use pocketmine\player\Player;

abstract class TextType
{
    public const KEY_TYPE = "type";

    public const TYPE_CHAT = 0;
    public const TYPE_POPUP = 1;
    public const TYPE_TIP = 2;
    public const TYPE_JUKEBOX_POPUP = 3;
    public const TYPE_TITLE = 4;
    public const TYPE_PLAYER_CHAT = 5;
    public const TYPE_PLAYER_WHISPER = 6;
    public const TYPE_ACTIONBAR = 7;
    public const TYPE_TOAST = 8;

    public function __construct(private readonly int $type)
    {

    }

    public static function fromArray(array $data): self
    {
        return match ($data[self::KEY_TYPE]) {
            self::TYPE_CHAT => ChatText::fromArray($data),
            self::TYPE_POPUP => PopupText::fromArray($data),
            self::TYPE_TIP => TipText::fromArray($data),
            self::TYPE_JUKEBOX_POPUP => JukeboxPopupText::fromArray($data),
            self::TYPE_TITLE => TitleText::fromArray($data),
            self::TYPE_PLAYER_CHAT => PlayerChatText::fromArray($data),
            self::TYPE_PLAYER_WHISPER => PlayerWhisperText::fromArray($data),
            self::TYPE_ACTIONBAR => ActionBarText::fromArray($data),
            self::TYPE_TOAST => ToastText::fromArray($data),
            default => throw new InvalidArgumentException("Invalid text type"),
        };
    }

    /**
     * @param Player[] $recipients
     */
    abstract public function handle(array $recipients): void;

    /**
     * @return array<string, mixed>
     */
    public function getArray(): array
    {
        return [
            self::KEY_TYPE => $this->type
        ];
    }
}