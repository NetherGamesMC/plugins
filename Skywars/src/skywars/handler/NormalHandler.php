<?php

declare(strict_types=1);

namespace skywars\handler;

use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\utils\TextFormat;

class NormalHandler extends BaseHandler
{
    public function startGame(): void
    {
        parent::startGame();

        $arena = $this->arena;

        $arena->broadcastMessage(TextUtils::center('Skywars'), true);
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Gather resources and equipment on your island'), true);
        $arena->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'in order to eliminate every other person!'), true);
        if (($credits = $this->plugin->getArenaConfig()->getCredits($arena)) !== null) {
            $arena->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . $arena->getMapDisplayName() . ", by " . $credits), true);
        }
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);

        if ($arena->isSoloGame()) {
            $arena->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Teaming is not allowed on Solo mode!', true);
        } else {
            $arena->broadcastMessage('§cCross-teaming with other teams or attacking other team members is not allowed in this game. You will be banned if you attempt or threaten to do so.', true);
        }

        $array = [
            11 => '',
            10 => CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . 'Opens in 0:10',
            9 => '',
            8 => CustomIcon::PLAYERS_TINY . ' ' . TextFormat::GREEN . count($arena->getAlivePlayers()),
            7 => '',
            6 => CustomIcon::KILLS . ' ' . TextFormat::GREEN . 0,
            5 => '',
            4 => CustomIcon::MAP . ' ' . TextFormat::GREEN . $arena->getMapDisplayName(),
            3 => CustomIcon::GAMEMODE . ' ' . TextFormat::GREEN . $arena->getTypeName(),
            2 => '',
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
        ];
        $arena->getScoreboard()->setLines($arena->getPlayers(), $array);
    }
}