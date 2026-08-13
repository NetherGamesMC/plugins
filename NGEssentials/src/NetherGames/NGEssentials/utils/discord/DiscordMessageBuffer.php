<?php

namespace NetherGames\NGEssentials\utils\discord;

use libDiscord\DiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Footer;
use libDiscord\message\embed\MessageEmbed;
use pocketmine\utils\TextFormat;
use function implode;
use function strlen;

class DiscordMessageBuffer
{
    /** @var string[] */
    private array $messages = [];
    /** @var int */
    private int $bufferSize = 0;

    public function __construct(private DiscordChannel $channel, private string $title, private string $footer = "")
    {

    }

    public function add(string $message): bool
    {
        if ($this->bufferSize + strlen($message) + 1 > MessageEmbed::DESCRIPTION_MAX_LENGTH) {
            $this->sendBuffer();
        }

        $this->messages[] = $message;
        $this->bufferSize += strlen($message) + 1;

        return true;
    }

    public function sendBuffer(): void
    {
        if (count($this->messages) === 0) {
            return;
        }

        $this->channel->post(DiscordMessage::embed(MessageEmbed::rich($this->title)
            ->setDescription(implode(TextFormat::EOL, $this->messages))
            ->setFooter(
                Footer::simple($this->footer, '')
            )
        ));

        $this->bufferSize = 0;
        $this->messages = [];
    }
}