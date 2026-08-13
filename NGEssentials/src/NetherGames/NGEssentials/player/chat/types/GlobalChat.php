<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

use libDiscord\LimitAvoidableDiscordChannel;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\player\chat\ChatManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\discord\DiscordMessageBuffer;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function date;
use function in_array;

class GlobalChat extends ChatType
{
    /** @var DiscordMessageBuffer */
    private DiscordMessageBuffer $messageBuffer;

    public function __construct(private ChatManager $chatManager)
    {
        parent::__construct('Global Chat');

        $this->messageBuffer = new DiscordMessageBuffer(
            new LimitAvoidableDiscordChannel([]),
            $this->getDisplayName(),
            $chatManager->getPlugin()->getServerManager()->getUniqueId()
        );
    }

    public function broadcast(Player $player, string $message): void
    {
        $plugin = $this->getChatManager()->getPlugin();
        $playerData = $plugin->getPlayerData();

        if (!$playerData->getBool($player, PlayerData::GLOBAL_CHAT)) {
            $player->sendMessage(TextFormat::RED . "You have disabled global chat.");
            $player->sendMessage(TextFormat::YELLOW . "Run /cfx to turn on global chat.");
            return;
        }

        $serverManager = $plugin->getServerManager();
        $enforcementHandler = $plugin->getPlayerManager()->getEnforcementHandler();
        $server = $plugin->getServer();

        $isSpectator = $playerData->getBool($player, PlayerData::TRACK);

        if ($isSpectator) {
            $nametag = TextFormat::RED . TextFormat::BOLD . "SPECTATOR " . TextFormat::RESET . TextFormat::GRAY . $player->getDisplayName() . TextFormat::RESET;
        } else {
            $nametag = $player->getNameTag();
            if ($nametag === '') {
                $nametag = $playerData->getString($player, PlayerData::RANKTAG);
            }
        }

        $perWorldChat = !in_array($serverManager->getServerType(), [ServerManager::CREATIVE, ServerManager::FACTIONS, ServerManager::SB, ServerManager::LOBBY], true);
        $recipients = $perWorldChat ? $player->getWorld()->getPlayers() : $server->getOnlinePlayers();

        $ev = new NGChatEvent($player, $nametag, $message, array_filter($recipients, fn(Player $recipient) => $playerData->getBool($recipient, PlayerData::GLOBAL_CHAT)));
        $ev->call();

        if (!$ev->isCancelled()) {
            /** @var NGPlayer $player */
            $player->sendChat($ev->getPrefix() . $ev->getDisplayName() . $ev->getSplitter() . $ev->getMessage(), $ev->getRecipients());

            $enforcementHandler->sendRelayMessage($ev->getStaffPrefix() . $player->getName() . '§r: ' . $ev->getMessage(), $ev->getRecipients(), $player->getWorld());
            $this->messageBuffer->add('**[' . date('H:i:s') . '] ** - ' . TextFormat::clean($ev->getPrefix() . $player->getName() . $ev->getSplitter() . $ev->getMessage()));

            $this->sendEntry($player, $ev->getMessage(), 'global');
        }
    }

    /**
     * @return ChatManager
     */
    public function getChatManager(): ChatManager
    {
        return $this->chatManager;
    }
}