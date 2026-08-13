<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\player\chat\kafka\message\Message;

abstract class SingleMessageText extends TextType
{
    public const KEY_MESSAGE = "message";

    public function __construct(int $type, protected readonly Message $message)
    {
        parent::__construct($type);
    }

    /**
     * @inheritDoc
     */
    public function getArray(): array
    {
        return [
            ...parent::getArray(),
            self::KEY_MESSAGE => $this->message->getArray()
        ];
    }
}