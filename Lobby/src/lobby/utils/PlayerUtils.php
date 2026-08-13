<?php
declare(strict_types=1);

namespace lobby\utils;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

class PlayerUtils
{
    public static function playSound(Player $player, string $soundName, int $pitch): void
    {
        $player->getNetworkSession()->sendDataPacket(
            PlaySoundPacket::create(
                $soundName,
                $player->getLocation()->getX(),
                $player->getLocation()->getY(),
                $player->getLocation()->getZ(),
                100,
                $pitch,
                null
            )
        );
    }

    public static function hasUnlockedToken(Player $player, string $tokenId): bool
    {
        return in_array($tokenId, NGEssentials::getInstance()->getPlayerData()->getArray($player, PlayerData::LOBBY_COLLECTED_TOKENS));
    }
}