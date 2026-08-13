<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\elasticsearch\ElasticSearch;
use NetherGames\NGEssentials\elasticsearch\entry\IndexEntry;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_replace;
use function date;

abstract class ChatType
{
    public const ES_INDEX = 'chat';

    public function __construct(
        private string $displayName
    )
    {
    }

    public function canBeUsed(Player $player): bool
    {
        return true;
    }

    abstract public function broadcast(Player $player, string $message): void;

    /**
     * @param array<string, string|int|bool|array> $extraData
     */
    public function sendEntry(Player $player, string $message, string $context, array $extraData = []): void
    {
        ElasticSearch::getInstance()->addEntry(new IndexEntry(
            index: self::ES_INDEX,
            data: array_replace(
                [
                    'content' => TextFormat::clean($message),
                    'sender_name' => $player->getName(),
                    'sender_xuid' => $player->getXuid(),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'server_id' => ServerManager::getServerUniqueId(),
                    'context' => $context,
                ],
                $extraData
            )
        ));
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}