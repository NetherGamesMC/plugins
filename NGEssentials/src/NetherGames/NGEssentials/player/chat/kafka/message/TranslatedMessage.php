<?php

namespace NetherGames\NGEssentials\player\chat\kafka\message;

use NetherGames\NGEssentials\player\Translator;
use pocketmine\player\Player;

class TranslatedMessage extends Message
{
    public const KEY_MESSAGE_TYPE = "messageType";
    public const KEY_TRANSLATION_KEY = "translationKey";
    public const KEY_VARIABLES = "variables";

    public function __construct(private readonly string $translationKey, private readonly int $messageType, private readonly array $variables = [])
    {
        parent::__construct(Message::MESSAGE_TRANSLATED);
    }

    public static function fromArray(array $array): self
    {
        return new self($array[self::KEY_TRANSLATION_KEY], $array[self::KEY_MESSAGE_TYPE], $array[self::KEY_VARIABLES]);
    }

    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_MESSAGE_TYPE => $this->messageType,
            self::KEY_TRANSLATION_KEY => $this->translationKey,
            self::KEY_VARIABLES => $this->variables,
        ];
    }

    public function getMessage(Player $player): string
    {
        return Translator::getTranslationPlayer($player, $this->translationKey, $this->messageType, ...$this->variables);
    }
}