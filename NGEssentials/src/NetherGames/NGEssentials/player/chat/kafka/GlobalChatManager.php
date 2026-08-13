<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat\kafka;


use InvalidArgumentException;
use JsonException;
use NetherGames\NGEssentials\kafka\KafkaPublisher;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\channel\ChatChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\GlobalChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\GuildChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\PrivateChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\RankedChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\ReportsChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\ServerTypeChannel;
use NetherGames\NGEssentials\player\chat\kafka\channel\StaffChannel;
use NetherGames\NGEssentials\player\chat\kafka\type\TextType;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerManager;
use RdKafka\Message;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class GlobalChatManager
{
    private const MESSAGES_TOPIC = "ess_messages";

    /** @var ?KafkaPublisher */
    private ?KafkaPublisher $publisher;
    /** @var array<int, ChatChannel> */
    private array $channels = [];

    public function __construct(PlayerManager $playerManager)
    {
        $this->registerChannels($playerManager);
        $this->registerKafkaTopic($plugin = $playerManager->getPlugin());

        $this->publisher = $plugin->getPublisher();
    }

    private function registerChannels(PlayerManager $playerManager): void
    {
        $plugin = $playerManager->getPlugin();
        $server = $plugin->getServer();

        $this->registerChannel(new GlobalChannel($server));
        $this->registerChannel(new GuildChannel($plugin));
        $this->registerChannel(new PrivateChannel($playerManager));
        $this->registerChannel(new RankedChannel($plugin));
        $this->registerChannel(new ReportsChannel($plugin));
        $this->registerChannel(new StaffChannel(ChatChannel::CHANNEL_STAFF, $plugin, Permissions::STAFF_RANKS));
        $this->registerChannel(new StaffChannel(ChatChannel::CHANNEL_TRAINEE, $plugin, [Permissions::RANK_TRAINEE]));
        $this->registerChannel(new StaffChannel(ChatChannel::CHANNEL_MODERATION, $plugin, [Permissions::RANK_CREW]));
        $this->registerChannel(new StaffChannel(ChatChannel::CHANNEL_ADMIN, $plugin, [Permissions::RANK_ADMIN]));
        $this->registerChannel(new ServerTypeChannel($plugin));
    }

    public function registerChannel(ChatChannel $channel): void
    {
        $channelId = $channel->getChannelId();

        if (isset($this->channels[$channelId])) {
            throw new InvalidArgumentException("Channel with id $channelId already registered");
        }

        $this->channels[$channelId] = $channel;
    }

    private function registerKafkaTopic(NGEssentials $plugin): void
    {
        $plugin->getConsumer()?->addTopic(self::MESSAGES_TOPIC, function (Message $message) use ($plugin): void {
            $payload = json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR);
            if ($payload === null) {
                $plugin->getLogger()->warning("Failed to decode message payload: " . $message->payload);
                return;
            }

            [$channelId, $channelKey] = ChatChannel::getKeys($message->key);
            $channel = $this->getChannel($channelId);

            if ($channel === null) {
                $plugin->getLogger()->warning("Received message for unknown channel: " . $message->key);
                return;
            }

            TextType::fromArray($payload)->handle($channel->getRecipients($channelKey));
        });
    }

    public function getChannel(int $channelId): ?ChatChannel
    {
        return $this->channels[$channelId] ?? null;
    }

    public function sendGuildMessage(TextType $textType, int $guildId): void
    {
        /** @var GuildChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_GUILD);

        $this->send($channel->getKey($guildId), $textType);
    }

    public function send(string $channel, TextType $textType): void
    {
        try {
            $this->publisher?->publishMessage(self::MESSAGES_TOPIC, json_encode($textType->getArray(), JSON_THROW_ON_ERROR), $channel);
        } catch (JsonException $e) {
            NGEssentials::getInstance()->getLogger()->error("Failed to send message: " . $e->getMessage());
        }
    }

    public function sendServerTypeMessage(TextType $textType, string $serverType): void
    {
        /** @var ServerTypeChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_SERVER_TYPE);

        $this->send($channel->getKey($serverType), $textType);
    }

    public function sendStaffMessage(TextType $textType): void
    {
        /** @var StaffChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_STAFF);

        $this->send($channel->getKey(), $textType);
    }

    public function sendModerationMessage(TextType $textType): void
    {
        /** @var StaffChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_MODERATION);

        $this->send($channel->getKey(), $textType);
    }

    /**
     * @param string[] $receivers
     */
    public function sendPrivateMessage(TextType $textType, array $receivers): void
    {
        /** @var PrivateChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_PRIVATE);

        if (count($receivers) === 0) {
            return;
        }

        $this->send($channel->getKey($receivers), $textType);
    }

    public function sendReportsMessage(TextType $textType): void
    {
        /** @var ReportsChannel $channel */
        $channel = $this->getChannel(ChatChannel::CHANNEL_REPORTS);

        $this->send($channel->getKey(), $textType);
    }
}