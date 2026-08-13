<?php

namespace NetherGames\NGEssentials\player\chat\kafka\type;

use NetherGames\NGEssentials\player\chat\kafka\message\Message;
use pocketmine\player\Player;

class TitleText extends TextType
{
    public const KEY_TITLE = "title";
    public const KEY_SUBTITLE = "subtitle";
    public const KEY_FADE_IN = "fadeIn";
    public const KEY_STAY = "stay";
    public const KEY_FADE_OUT = "fadeOut";

    public function __construct(
        private readonly Message  $title,
        private readonly ?Message $subtitle = null,
        private readonly int      $fadeIn = -1,
        private readonly int      $stay = -1,
        private readonly int      $fadeOut = -1
    )
    {
        parent::__construct(self::TYPE_TITLE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Message::fromArray($data[self::KEY_TITLE]),
            $data[self::KEY_SUBTITLE] ? Message::fromArray($data[self::KEY_SUBTITLE]) : null,
            $data[self::KEY_FADE_IN],
            $data[self::KEY_STAY],
            $data[self::KEY_FADE_OUT]
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
            self::KEY_SUBTITLE => $this->subtitle,
            self::KEY_FADE_IN => $this->fadeIn,
            self::KEY_STAY => $this->stay,
            self::KEY_FADE_OUT => $this->fadeOut
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $recipient->sendTitle(
                $this->title->getMessage($recipient),
                $this->subtitle?->getMessage($recipient) ?? "",
                $this->fadeIn,
                $this->stay,
                $this->fadeOut
            );
        }
    }
}