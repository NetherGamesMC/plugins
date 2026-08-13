<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\social;

use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\player\chat\kafka\type\TextType;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\guilds\GuildManager;
use NetherGames\NGEssentials\player\social\guilds\objects\Guild;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Throwable;

class SocialListener implements Listener
{

    public function __construct(private SocialManager $socialManager)
    {
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        if ($item->equals(LobbyItems::getSocialItem())) {
            $this->getSocialManager()->sendSocialMenu($player);

            return true;
        }

        return false;
    }

    public function getSocialManager(): SocialManager
    {
        return $this->socialManager;
    }

    /**
     * @param PlayerItemUseEvent $event
     *
     * @priority LOW
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        if ($this->onItemInteract($event->getPlayer(), $event->getItem())) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     *
     * @priority LOW
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Utils::hasClassicUI($player)) {
            $this->onItemInteract($player, $event->getItem());
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        $socialManager = $this->getSocialManager();
        $playerData = $socialManager->getPlugin()->getPlayerData();
        $guildManager = $socialManager->getGuildsManager();
        $partyManager = $socialManager->getPartyManager();
        $friendsManager = $socialManager->getFriendsManager();

        if (($party = $partyManager->getParty($player)) !== null) {
            $partyManager->leaveParty($party, $player);
        }

        $partyManager->removeInvites($player->getName());

        $guild = $guildManager->getGuild($playerData->getInt($player, PlayerData::GUILD));
        if (!$playerData->getBool($player, PlayerData::TRANSFER)) {
            $playerName = $socialManager->getManager()->getPlayerColouredName($player, TextFormat::YELLOW, true);
            $message = $playerName . TextFormat::YELLOW . ' left.';

            if ($guild !== null) {
                $guildManager->sendGuildMessage($guild, $message);
            }

            $friendsManager->sendFriendMessage($player, $message);
        }

        if ($guild !== null) {
            $guildManager->collectGarbage($player, $guild);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority LOWEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player && $event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player && !$event instanceof EntityDamageByChildEntityEvent && $damager->getInventory()->getItemInHand()->equals(LobbyItems::getSocialItem(), false, false)) {
                $this->getSocialManager()->sendRequestMenu($damager, $entity);
            }
        }
    }

    /**
     * @param NGJoinEvent $event
     *
     * @priority LOW
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $socialManager = $this->getSocialManager();

        if (MySQLCredentials::isDatabaseOnline()) {
            $preloaded = $event->isPreLoaded();

            $oldPlayerName = $socialManager->getPlugin()->getPlayerData()->getString($player, PlayerData::OLD_PLAYER_NAME);
            $this->getSocialManager()->getGuildsManager()->loadGuildByPlayer($player, function (?Guild $guild) use ($player, $oldPlayerName, $preloaded): void {
                if ($player->isClosed()) {
                    return;
                }

                $gm = $this->getSocialManager()->getGuildsManager();

                // Always update chat property for guilds, we can't always be consistence on all servers, lag can always
                // occur when the player is no longer in guild and this guild chat property is not updated during that.
                if ($guild === null) {
                    return;
                }

                // Player changed their in-game name, update appropriately.
                if (!empty($oldPlayerName)) {
                    $guild->updatePlayerName($player->getName(), $oldPlayerName, true);
                }

                // Now if the data is not preloaded, we send the join message to all players in the guild.
                if (!$preloaded) {
                    $playerName = $this->getSocialManager()->getPlugin()->getPlayerManager()->getPlayerColouredName($player, TextFormat::YELLOW, true);
                    $gm->sendGuildMessage($guild, $playerName . TextFormat::YELLOW . ' joined.');

                    if (!empty(ltrim($guild->getMotd()))) {
                        $player->sendMessage(TextFormat::DARK_GREEN . "Guild motd > " . TextFormat::WHITE . $guild->getMotd());
                    }

                    // Check if guild should be enabled/disabled based on leaders Legend rank
                    $isPlayerTheLeader = $guild->getGuildRole($player) === Guild::RANK_LEADER;

                    if ($isPlayerTheLeader) {
                        // Player is the leader -> check their permission directly
                        $hasLegendRank = $player->hasPermission(Permissions::RANK_LEGEND);
                        $this->checkGuildDisable($guild, $gm, $player, $hasLegendRank);
                    } else {
                        // Player is not the leader -> load leader's data to check their rank
                        $leaderName = $guild->getLeader();
                        MySQLCredentials::executeSelect('player.load_permissions_and_extra_friends', ['player' => $leaderName], function (array $rows) use ($guild, $gm, $player): void {
                            if (empty($rows)) {
                                return;
                            }
                            $row = $rows[0];
                            $permissions = array_reduce(explode(',', $row['permissions']), static function (array $carry, string $permission): array {
                                $carry[$permission] = true;
                                return $carry;
                            }, []);
                            [$effectivePermissions,] = $this->getSocialManager()->getPlugin()->getPlayerManager()->getRankManager()->getPermissions($permissions, explode(',', $row['rank']), $row['status_credits'], $row['titan_expire'], $row['vote_time']);
                            $hasLegendRank = isset($effectivePermissions[Permissions::RANK_LEGEND]);
                            $this->checkGuildDisable($guild, $gm, $player, $hasLegendRank);
                        }, function (Throwable $error): void {
                            $this->getSocialManager()->getPlugin()->getLogger()->logException($error);
                        });
                    }
                }
            });

            if (!$preloaded) {
                $friendsManager = $socialManager->getFriendsManager();
                $friendsManager->loadRelations($player->getName(), function () use ($player, $friendsManager): void {
                    $playerName = $friendsManager->getSocialManager()->getManager()->getPlayerColouredName($player, TextFormat::YELLOW, true);
                    $friendsManager->sendFriendMessage($player, $playerName . TextFormat::YELLOW . ' joined.');
                });
            }
        }
    }

    private function checkGuildDisable(Guild $guild, GuildManager $gm, NGPlayer $player, bool $hasLegendRank): void
    {
        $isPlayerTheLeader = $guild->getGuildRole($player) === Guild::RANK_LEADER;
        $guildShouldBeDisabled = !$hasLegendRank;
        $guildCurrentlyDisabled = $guild->isDisabled();

        if ($guildShouldBeDisabled !== $guildCurrentlyDisabled) {
            if ($guildShouldBeDisabled) {
                $guild->setDisabled(true, true);

                if ($isPlayerTheLeader) {
                    $player->sendConditionalMessage("§cYour guild has been disabled as you no longer own a §l§bLEGEND §r§cor §l§cTITAN §r§crank. Buy the rank at §bngmc.co/store §cto re-enable it again!", TextType::TYPE_CHAT);
                }
                $gm->sendGuildMessage($guild, "§cYour guild has been disabled as the leader no longer owns a §l§bLEGEND §r§cor §l§cTITAN §r§crank.");
            } else {
                $guild->setDisabled(false, true);

                if ($isPlayerTheLeader) {
                    $player->sendConditionalMessage('§aYour guild has now been re-enabled due to you reobtaining a §l§bLEGEND §r§aor §l§cTITAN §r§arank.', TextType::TYPE_CHAT);
                }
                $gm->sendGuildMessage($guild, '§aGuild §6' . $guild->getGuildName() . ' §ahas now been re-enabled due to the leader reobtaining a §l§bLEGEND §r§aor §l§cTITAN §r§arank.');
            }
        }
    }
}