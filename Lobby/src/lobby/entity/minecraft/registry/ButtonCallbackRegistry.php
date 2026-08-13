<?php
declare(strict_types=1);

namespace lobby\entity\minecraft\registry;

use lobby\utils\npc\Button;
use pocketmine\player\Player;

class ButtonCallbackRegistry
{
    private static array $actions = [];

    public static function registerForPlayer(Player $player, array $actions): void
    {
        self::$actions[$player->getXuid()] = $actions;
    }

    /**
     * @param Player $player
     * @param int $index
     * @return Button|null
     */
    public static function getAction(Player $player, int $index): ?Button
    {
        if (array_key_exists($player->getXuid(), self::$actions)) {
            return self::$actions[$player->getXuid()][$index];
        }

        return null;
    }
}