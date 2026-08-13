<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use InvalidArgumentException;
use libDiscord\DiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Field;
use libDiscord\message\embed\MessageEmbed;
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\PlayerChatText;
use NetherGames\NGEssentials\utils\discord\DiscordUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class ServerWideChat extends ChatType
{
    /** @var ChatChannel */
    private readonly ChatChannel $chatChannel;
    /** @var ?DiscordChannel */
    private readonly ?DiscordChannel $discordChannel;

    public function __construct(
        string                       $displayName,
        private readonly int         $channelId,
        private readonly ChatManager $chatManager,
        private readonly string      $webhookId = ''
    )
    {
        parent::__construct($displayName);
        $channel = $this->chatManager->getGlobalChatManager()->getChannel($this->channelId);

        if ($channel === null) {
            throw new InvalidArgumentException("Invalid channel id");
        }

        $this->chatChannel = $channel;
        $this->discordChannel = $this->webhookId === '' ? null : new DiscordChannel($this->webhookId);
    }

    public function broadcast(Player $player, string $message): void
    {
        $playerManager = $this->getChatManager()->getPlugin()->getPlayerManager();
        $realPlayerName = $playerManager->getPlayerColouredName($player, TextFormat::GRAY, true);

        $this->chatManager->getGlobalChatManager()->send(
            $this->getKey($player),
            new PlayerChatText(
                new RawMessage($this->getPrefix() . $realPlayerName . '§r: ' . $message),
                $player->getXuid()
            )
        );

        $this->discordChannel?->post(DiscordMessage::embed(MessageEmbed::rich("Chat: " . $this->getDisplayName())
            ->addFields(
                Field::simple("Player", $player->getName()),
                Field::simple("Message", TextFormat::clean($message))
            )
            ->setThumbnail(DiscordUtils::asThumbnail($player->getName()))
        ));
    }

    /**
     * @return ChatManager
     */
    public function getChatManager(): ChatManager
    {
        return $this->chatManager;
    }

    abstract protected function getKey(Player $player): string;

    abstract public function getPrefix(): string;

    public function canBeUsed(Player $player): bool
    {
        return $this->chatChannel->canBeUsed($player);
    }

    protected function getChatChannel(): ChatChannel
    {
        return $this->chatChannel;
    }
}