<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat;


use NetherGames\NGEssentials\player\chat\emojis\Emojis;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function preg_replace;
use function strlen;
use function strtolower;

class ChatListener implements Listener
{

    public function __construct(private ChatManager $chatManager)
    {
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $this->getChatManager()->getFilter()->unsetChatData($event->getPlayer());
    }

    /**
     * @return ChatManager
     */
    public function getChatManager(): ChatManager
    {
        return $this->chatManager;
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage(), !$player->hasPermission(Permissions::RANK_OWNER));
        $filter = $this->getChatManager()->getFilter();

        $characters = strlen($event->getMessage());
        if (($characters > 2) && strlen(preg_replace('![^A-Z]+!', '', $message)) >= ($characters / 2)) {
            $message = strtolower($message);
        }

        if ($player->hasPermission(Permissions::BYPASS_CHAT_FILTER)) {
            $this->sendPlayerChat($player, $message);
        } else {
            if (!$filter->checkSpam($player, $message) || !$filter->checkAdvertising($player, $message)) {
                $event->cancel();
                return;
            }

            $filter->checkSwearing($player, $message, function () use ($player, $message): void {
                $this->sendPlayerChat($player, $message);
            });
        }

        $event->cancel();
    }

    private function sendPlayerChat(Player $player, string $message): void
    {
        $playerData = $this->getChatManager()->getPlugin()->getPlayerData();
        $message = Emojis::getInstance()->replace($message);

        if (!$playerData->getBool($player, PlayerData::NICK) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK) {
            $message = ChatColors::getInstance()->getColor($player, $playerData->getInt($player, PlayerData::CHAT_COLOR))->formatText($message);
        }

        $chatTypes = ChatTypes::getInstance();
        $chatType = $playerData->getInt($player, PlayerData::CHAT_TYPE);
        $globalChat = $chatTypes->getChatType(ChatTypes::GLOBAL_CHAT);

        if ($chatType === ChatTypes::GLOBAL_CHAT) {
            $globalChat->broadcast($player, $message);
        } elseif (str_starts_with(TextFormat::clean($message), '!')) {
            $globalChat->broadcast($player, preg_replace('/!/', '', $message, 1));
        } else {
            $type = $chatTypes->getChatType($chatType);

            if ($type->canBeUsed($player)) {
                $type->broadcast($player, $message);
            } else {
                $playerData->setValue($player, PlayerData::CHAT_TYPE, ChatTypes::GLOBAL_CHAT);
                $globalChat->broadcast($player, $message);
            }
        }
    }
}