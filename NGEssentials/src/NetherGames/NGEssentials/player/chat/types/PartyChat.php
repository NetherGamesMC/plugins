<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use NetherGames\NGEssentials\player\social\party\PartyManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PartyChat extends ChatType
{
    public const PREFIX = TextFormat::AQUA . 'PARTY' . TextFormat::RESET . ' » ';
    public const RELAY_PREFIX = TextFormat::AQUA . 'PARTY RELAY' . TextFormat::RESET . ' » ';

    public function __construct(private PartyManager $partyManager)
    {
        parent::__construct('Party Chat');
    }

    public function canBeUsed(Player $player): bool
    {
        return $this->getPartyManager()->getParty($player) !== null;
    }

    private function getPartyManager(): PartyManager
    {
        return $this->partyManager;
    }

    public function broadcast(Player $player, string $message): void
    {
        $partyManager = $this->getPartyManager();
        $socialManager = $partyManager->getSocialManager();
        $playerManager = $socialManager->getManager();

        /** @var Party $party */
        $party = $partyManager->getParty($player);

        $this->sendEntry($player, $message, 'party', [
            'party_leader' => $party->getLeaderName()
        ]);

        $realPlayerName = $playerManager->getPlayerColouredName($player, TextFormat::GRAY, true);
        $players = $partyManager->getPlayers($party);
        $message = $realPlayerName . '§r: ' . $message;

        $player->getServer()->broadcastMessage(self::PREFIX . $message, $players);
        $playerManager->getEnforcementHandler()->sendRelayMessage(self::RELAY_PREFIX . $message, $players);
    }
}