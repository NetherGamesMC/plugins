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

namespace NetherGames\NGEssentials\player\social\friends;

use Closure;
use libforms\elements\Button;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\FormManager;
use libforms\SimpleForm;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\kafka\message\RawMessage;
use NetherGames\NGEssentials\player\chat\kafka\type\ChatText;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Server;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use poggit\libasynql\SqlError;
use RdKafka\Message;
use skyblock\SkyBlock;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function count;
use function explode;
use function implode;
use function in_array;

class FriendsManager
{
    private const FRIENDS_TOPIC = "ess_friends";

    public const RELATION_REQUEST = 0;
    public const RELATION_FRIEND = 1;

    private const PREFIX = TextFormat::GREEN . 'Friend > ' . TextFormat::RESET;

    public function __construct(private SocialManager $socialManager)
    {
        $this->registerKafkaTopic($socialManager->getPlugin());
    }

    private function registerKafkaTopic(NGEssentials $plugin): void
    {
        $plugin->getConsumer()?->addTopic(self::FRIENDS_TOPIC, function (Message $message): void {
            [$player_one, $player_two] = explode(":", $message->key);
            $status = (int)$message->payload;

            if ($status === -1) {
                $this->removeRelation($player_one, $player_two, false);
            } else {
                $this->setRelation($player_one, $player_two, $status, false);
            }
        });
    }

    /**
     * @param Player $player
     * @param callable $onComplete function(PlayerFriendsInfo[] $friendsInfo): void
     */
    public function getFriendsInfo(Player $player, callable $onComplete): void
    {
        Utils::validateCallableSignature(function (array $friendsInfo): void {}, $onComplete);

        $friends = $this->getFriends($player);

        /** @var PlayerFriendsInfo[] $friendsInfo */
        $friendsInfo = [];

        foreach ($friends as $friendName) {
            if ($player->getServer()->getPlayerExact($friendName) !== null) {
                $friendInfo = new PlayerFriendsInfo();
                $friendInfo->playerName = $friendName;
                $friendInfo->online = true;
                $friendInfo->server = null;

                $friendsInfo[] = $friendInfo;
            }
        }

        $callable = function (array $info) use ($player, $friendsInfo, $onComplete): void {
            /**
             * @param ?PlayerSocialInfo[] $info
             * @phpstan-param array<string, PlayerSocialInfo|null> $info
             */
            if (!$player->isConnected()) {
                return;
            }

            $offlineFriends = [];
            $serverManager = $this->getSocialManager()->getPlugin()->getServerManager();

            foreach ($info as $identifier => $i) {
                if ($i === null) {
                    $offlineFriends[] = $identifier;
                } else {
                    $friend = $i->playerName;
                    $server = $serverManager->getServer($i->location);

                    if ($server === null) {
                        $offlineFriends[] = $friend;
                    } else {
                        $friendInfo = new PlayerFriendsInfo();
                        $friendInfo->playerName = $friend;
                        $friendInfo->online = true;
                        $friendInfo->server = $server;

                        $friendsInfo[] = $friendInfo;
                    }
                }
            }

            foreach ($offlineFriends as $friend) {
                $friendInfo = new PlayerFriendsInfo();
                $friendInfo->playerName = $friend;
                $friendInfo->online = false;
                $friendInfo->server = null;

                $friendsInfo[] = $friendInfo;
            }

            $onComplete($friendsInfo);
        };

        $otherFriends = array_diff($friends, array_map(fn(PlayerFriendsInfo $info): string => $info->playerName, $friendsInfo));
        if (count($otherFriends) === 0) {
            $callable([]);
        } else {
            SocialManager::requestPlayerInfos($otherFriends, $callable);
        }
    }

    public function sendFriendMenu(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $this->getFriendsInfo($player, function (array $friendsInfo) use ($player, $form, $onBack): void {
                if (!$player->isConnected()) {
                    return;
                }

                /**
                 * @param PlayerFriendsInfo[] $friendsInfo
                 */
                $goBack = function (Player $player) use ($onBack): void {
                    $this->sendFriendMenu($player, $onBack);
                };

                $form->setTitle('Friends');
                $form->setBackClosure($onBack);

                $onlineFriends = array_filter($friendsInfo, fn(PlayerFriendsInfo $info): bool => $info->online);

                $form->addButton(new Button(TextFormat::YELLOW . 'Friend List' . TextFormat::EOL . TextFormat::GRAY . 'Online: ' . TextFormat::GREEN . (count($onlineFriends)) . TextFormat::GRAY . ' | Total: ' . TextFormat::GREEN . count($friendsInfo) . (($maxFriends = $this->getMaxFriends($player)) === -1 ? '' : '/' . $maxFriends), function (Player $player) use ($friendsInfo, $goBack) {
                    $this->sendFriendList($player, $friendsInfo, $goBack);
                }));

                $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                if ($playerData->getBool($player, PlayerData::FRIEND_REQUESTS)) {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Friend Requests' . TextFormat::EOL . TextFormat::GRAY . 'Current requests: ' . TextFormat::GREEN . count($requests = $this->getRequests($player)), function (Player $player) use ($requests, $goBack) {
                        $this->sendFriendRequests($player, $requests, $goBack);
                    }));
                } else {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Friend Requests' . TextFormat::EOL . TextFormat::RED . 'Disabled', function (Player $player) use ($playerData, $goBack) {
                        $playerData->setValue($player, PlayerData::FRIEND_REQUESTS, true);
                        Translator::sendMessage($player, "formhandler.settings.friends", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, "formhandler.settings.showing")]);

                        $goBack($player);
                    }));
                }

                $form->addButton(new Button(TextFormat::YELLOW . 'Add a friend', function (Player $player) use ($goBack) {
                    $this->sendAddFriendMenu($player, $goBack);
                }));

                $form->sendForm();
            });
        }
    }

    /**
     * @param Player $player
     * @return string[]
     */
    public function getFriends(Player $player): array
    {
        return $this->getRelation($player, self::RELATION_FRIEND);
    }

    /**
     * @param Player $player
     * @param int $relation
     *
     * @return string[]
     */
    private function getRelation(Player $player, int $relation): array
    {
        $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
        $relations = array_filter($playerData->getArray($player, PlayerData::RELATIONS), static function (int $playerRelation) use ($relation): bool {
            return $relation === $playerRelation;
        });

        return array_keys($relations);
    }

    public function getSocialManager(): SocialManager
    {
        return $this->socialManager;
    }

    /**
     * @return int<-1, max> Maximum number of friends, -1 if unlimited
     */
    private function getMaxFriendsByPermissions(Closure $permissionCheck, int $extraFriends): int
    {
        Utils::validateCallableSignature(function (string $permission): bool { return true; }, $permissionCheck);

        foreach (Permissions::STAFF_RANKS as $staffRank) {
            if ($permissionCheck($staffRank)) {
                return -1;
            }
        }

        return $extraFriends + match (true) {
                $permissionCheck(Permissions::RANK_LEGEND) => 50,
                $permissionCheck(Permissions::RANK_EMERALD) => 25,
                $permissionCheck(Permissions::RANK_ULTRA) => 15,
                default => 10,
            };
    }

    /**
     * @return int<-1, max> Maximum number of friends, -1 if unlimited
     */
    public function getMaxFriends(Player $player): int
    {
        return $this->getMaxFriendsByPermissions(
            fn(string $permission): bool => $player->hasPermission($permission),
            $this->getSocialManager()->getPlugin()->getPlayerData()->getInt($player, PlayerData::EXTRA_FRIENDS)
        );
    }

    private function getOfflineFriendsCount(string $playerName, Closure $callable): void
    {
        Utils::validateCallableSignature(function (?int $count): void {}, $callable);

        MySQLCredentials::executeSelect('player_relations.get_count', ['player' => $playerName, 'status' => self::RELATION_FRIEND], static function (array $rows) use ($callable): void {
            $callable((int)($rows[0]['count'] ?? null));
        }, static function (SqlError $error) use ($callable): void {
            $callable(null);
        });
    }

    /**
     * @param string $playerName
     * @param Closure $callable function(?int<-1, max> $maxFriends): void
     */
    private function getOfflineMaxFriends(string $playerName, Closure $callable): void
    {
        Utils::validateCallableSignature(function (?int $maxFriends): void {}, $callable);

        MySQLCredentials::executeSelect('player.load_permissions_and_extra_friends', ['player' => $playerName], function (array $rows) use ($callable): void {
            $maxFriends = null;

            if (isset($rows[0])) {
                $row = $rows[0];

                /** @var array<string, bool> $permissions */
                [$permissions,] = $this->getSocialManager()->getManager()->getRankManager()->getPermissions(
                    array_reduce(explode(',', $row['permissions']), static function (array $permissions, string $permission): array {
                        $permissions[$permission] = true;
                        return $permissions;
                    }, []),
                    explode(',', $row['rank']),
                    $row['status_credits'],
                    $row['titan_expire'],
                    $row['vote_time'],
                );

                $maxFriends = $this->getMaxFriendsByPermissions(
                    fn(string $permission): bool => $permissions[$permission] ?? false,
                    $row['extra_friends']
                );
            }

            $callable($maxFriends);
        }, static function (SqlError $error) use ($callable): void {
            $callable(null);
        });
    }

    /**
     * @param Player $player
     * @param (PlayerFriendsInfo[])|null $friendsInfo
     * @param callable|null $onBack
     */
    public function sendFriendList(Player $player, ?array $friendsInfo = null, ?callable $onBack = null): void
    {
        if ($friendsInfo === null) {
            $this->getFriendsInfo($player, function (array $friendsInfo) use ($player, $onBack): void {
                if ($player->isConnected()) {
                    $this->sendFriendList($player, $friendsInfo, $onBack);
                }
            });
            return;
        }

        FormManager::createSimplePaginatedForm($player, $friendsInfo, function (SimpleForm $form, int $page, array $friendsInfo) use ($onBack): void {
            /**
             * @param PlayerFriendsInfo[] $friendsInfo
             */
            $form->setTitle('Your Friends - Page ' . ($page + 1));
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($friendsInfo, $onBack): void {
                $this->sendFriendList($player, $friendsInfo, $onBack);
            };

            $serverManager = $this->getSocialManager()->getPlugin()->getServerManager();
            $server = $serverManager->getServer();

            foreach ($friendsInfo as $friend) {
                if ($friend->online) {
                    if ($friend->server === null) {
                        $form->addButton(new ImageButton(TextFormat::YELLOW . $friend->playerName . TextFormat::EOL . TextFormat::GREEN . 'Online on this server', ImageButton::IMAGE_TYPE_FACE, $friend->playerName, function (Player $player) use ($friend, $server, $goBack) {
                            $this->sendFriendSummary($player, $friend->playerName, $server, $goBack);
                        }));
                    } else {
                        $form->addButton(new ImageButton(TextFormat::YELLOW . $friend->playerName . TextFormat::EOL . TextFormat::GREEN . 'Online on ' . $friend->server->getCluster()->getName(), ImageButton::IMAGE_TYPE_FACE, $friend->playerName, function (Player $player) use ($friend, $goBack) {
                            $this->sendFriendSummary($player, $friend->playerName, $friend->server, $goBack);
                        }));
                    }
                } else {
                    $form->addButton(new ImageButton(TextFormat::YELLOW . $friend->playerName . TextFormat::EOL . TextFormat::RED . 'Offline', ImageButton::IMAGE_TYPE_FACE, $friend->playerName, function (Player $player) use ($friend, $goBack) {
                        $this->sendFriendSummary($player, $friend->playerName, null, $goBack);
                    }));
                }
            }
        }, 25);
    }

    public function sendFriendSummary(Player $player, string $friendName, ?Server $server = null, ?callable $onBack = null): void
    {
        $isOnline = $server !== null;
        $isOnlineOnServer = $isOnline && $server->equals($this->getSocialManager()->getPlugin()->getServerManager()->getServer());

        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Friend: ' . $friendName);
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack, $server, $friendName): void {
                $this->sendFriendSummary($player, $friendName, $server, $onBack);
            };

            if ($isOnlineOnServer) {
                $form->addButton(new Button(TextFormat::YELLOW . 'Invite to party' . TextFormat::EOL . TextFormat::GRAY . 'Send a party invite', function (Player $player) use ($friendName) {
                    if (($friend = $player->getServer()->getPlayerExact($friendName)) !== null && $friend->isConnected()) {
                        $this->getSocialManager()->getPartyManager()->invitePlayer($player, $friend);
                    } else {
                        $player->sendMessage(TextFormat::RED . 'An unexpected error occurred. Please try again later.');
                    }
                }));
            } elseif ($isOnline) {
                $form->addButton(new Button(TextFormat::YELLOW . 'Go to server' . TextFormat::EOL . TextFormat::GRAY . 'Transfers you to the player', function (Player $player) use ($server) {
                    $socialManager = $this->getSocialManager();
                    $serverType = $server->getCluster()->getServerType();

                    if ($serverType === ServerManager::REPLAY) {
                        $player->sendMessage(TextFormat::RED . "You can't teleport to someone watching a replay.");
                    } elseif ($serverType === ServerManager::SETUP) {
                        $player->sendMessage(TextFormat::RED . "You can't teleport to someone in a setup server.");
                    } else {
                        $socialManager->getManager()->transferPlayer($player, $server, '', true);
                    }
                }));
            }

            if ($isOnline) {
                $form->addButton(new Button(TextFormat::YELLOW . 'Spectate player' . TextFormat::EOL . TextFormat::GRAY . 'Watch in spectator mode', function (Player $player) use ($friendName) {
                    if ($player->hasPermission(Permissions::RANK_TITAN)) {
                        $manager = $this->getSocialManager()->getManager();
                        if ($manager->isInArena($player)) {
                            $player->sendMessage("§cYou can't spectate a player while being in-game.");
                        } else {
                            /** @var NGPlayer $player */
                            $manager->getEnforcementHandler()->setTracking($player, $friendName);
                        }
                    } else {
                        $player->sendMessage("§cYou don't have permission to spectate friends. Buy the §l§cTITAN §r§crank at §bngmc.co/store §cto use this feature!");
                    }
                }));
            }

            $form->addButton(new Button(TextFormat::YELLOW . 'Remove' . TextFormat::EOL . TextFormat::GRAY . 'Unfriend ' . $friendName, function (Player $player) use ($friendName, $goBack) {
                $this->sendRemoveFriend($player, $friendName, $goBack);
            }));

            $form->sendForm();
        }
    }

    public function sendRemoveFriend(Player $player, string $friendName, ?callable $onBack = null): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $form->setTitle(TextFormat::RED . 'Remove Friend');

            $form->setContent('Do you want to remove ' . $friendName . ' as a friend?');
            $form->setButton1(new Button(TextFormat::RED . 'Yes', function (Player $player) use ($friendName) {
                $this->removeRelation($player->getName(), $friendName);
                $player->sendMessage('§aYou have successfully unfriended §b' . $friendName . '§a.');

                $chatManager = $this->getSocialManager()->getManager()->getChatManager();
                $chatManager->sendGuaranteedMessage($friendName, new ChatText(new RawMessage(self::PREFIX . TextFormat::AQUA . $player->getName() . TextFormat::RED . ' removed you as a friend.')));
            }));
            $form->setButton2(new Button(TextFormat::GREEN . 'No', $onBack));

            $form->sendForm();
        }
    }

    public function removeRelation(string $player_one, string $player_two, bool $broadcast = true): void
    {
        $plugin = $this->getSocialManager()->getPlugin();

        $shouldBroadcast = 2;

        if (($p_one = $plugin->getServer()->getPlayerExact($player_one)) !== null) {
            $relation = $plugin->getPlayerData()->getArray($p_one, PlayerData::RELATIONS);
            unset($relation[$player_two]);
            $plugin->getPlayerData()->setValue($p_one, PlayerData::RELATIONS, $relation);

            $shouldBroadcast--;
        }

        if (($p_two = $plugin->getServer()->getPlayerExact($player_two)) !== null) {
            $relation = $plugin->getPlayerData()->getArray($p_two, PlayerData::RELATIONS);
            unset($relation[$player_one]);
            $plugin->getPlayerData()->setValue($p_two, PlayerData::RELATIONS, $relation);

            $shouldBroadcast--;
        }

        if ($broadcast) {
            MySQLCredentials::executeGeneric('player_relations.remove', ['player_one' => $player_one, 'player_two' => $player_two], function () use ($plugin, $shouldBroadcast, $player_one, $player_two): void {
                if ($plugin->getServerManager()->getServerType() === ServerManager::SB) {
                    $this->updateIslandMemberData($player_one, $player_two);
                }

                if ($shouldBroadcast > 0) {
                    $plugin->getPublisher()->publishMessage(self::FRIENDS_TOPIC, (string)-1, implode(':', [$player_one, $player_two]));
                }
            });
        } elseif ($plugin->getServerManager()->getServerType() === ServerManager::SB) {
            $this->updateIslandMemberData($player_one, $player_two);
        }
    }

    public function updateIslandMemberData(string $player_one, string $player_two): void
    {
        /** @var SkyBlock $skyblock */
        $skyblock = SkyBlock::getInstance();
        $islandManager = $skyblock->getIslandManager();
        if (($island = $islandManager->getIslandByOwner($player_one)) !== null) {
            $island->updateMembersData($island->getMembersData());
        }

        if (($island = $islandManager->getIslandByOwner($player_two)) !== null) {
            $island->updateMembersData($island->getMembersData());
        }
    }

    /**
     * Return the name of player that invite $invited
     *
     * @param Player $invited
     *
     * @return string[]
     */
    public function getRequests(Player $invited): array
    {
        return $this->getRelation($invited, self::RELATION_REQUEST);
    }

    public function sendFriendRequests(Player $player, array $requests, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Friend Requests');
            $form->setBackClosure($onBack);

            if (($requestCount = count($requests)) > 0) {
                $form->setContent('You currently have ' . $requestCount . ' pending friend requests:');

                foreach ($requests as $requester) {
                    $form->addButton(new ImageButton(TextFormat::YELLOW . $requester . TextFormat::EOL . TextFormat::GRAY . 'Accept or deny this request', ImageButton::IMAGE_TYPE_FACE, $requester, function (Player $player) use ($requester) {
                        $form = FormManager::createModalForm($player);

                        if ($form !== null) {
                            $form->setTitle('Friend Requests');

                            $form->setContent('Do you want to friend ' . $requester . '?');
                            $form->setButton1(new Button(TextFormat::GREEN . 'Yes', function (Player $player) use ($requester) {
                                if (in_array($requester, $this->getFriends($player), true)) {
                                    $player->sendMessage('§cYou\'re already friends with that player!');
                                } elseif (($maxFriends = $this->getMaxFriends($player)) !== -1 && count($this->getFriends($player)) >= $maxFriends) {
                                    $player->sendMessage(TextFormat::RED . "§cYou don't have permission to add more friends! Buy a rank at §bngmc.co/store §cto add more!");
                                } else {
                                    $socialManager = $this->getSocialManager();

                                    $acceptCallable = function (Player $player) use ($requester, $socialManager): void {
                                        $this->setRelation($playerName = $player->getName(), $requester, self::RELATION_FRIEND);

                                        $chatManager = $socialManager->getManager()->getChatManager();
                                        $chatManager->sendGuaranteedMessage($playerName, new ChatText(new RawMessage(self::PREFIX . TextFormat::GOLD . 'You are now friends with ' . TextFormat::AQUA . $requester . TextFormat::GOLD . '!')));
                                        $chatManager->sendGuaranteedMessage($requester, new ChatText(new RawMessage(self::PREFIX . TextFormat::AQUA . $playerName . TextFormat::GOLD . ' has accepted your friend request.')));
                                    };

                                    if (($p = $player->getServer()->getPlayerExact($requester)) === null) {
                                        $this->getOfflineMaxFriends($requester, function (?int $maxFriends) use ($player, $requester, $acceptCallable): void {
                                            if (!$player->isConnected()) {
                                                return;
                                            }

                                            if ($maxFriends === null) {
                                                $player->sendMessage(TextFormat::RED . 'An unexpected error occurred. Please try again later.');
                                            } else {
                                                $this->getOfflineFriendsCount($requester, function (?int $friendsCount) use ($player, $maxFriends, $acceptCallable): void {
                                                    if ($friendsCount === null) {
                                                        $message = TextFormat::RED . 'An unexpected error occurred. Please try again later.';
                                                    } elseif ($maxFriends !== -1 && $friendsCount >= $maxFriends) {
                                                        $message = '§cThat player can\'t add any more friends.';
                                                    } else {
                                                        $acceptCallable($player);
                                                        return;
                                                    }

                                                    /** @phpstan-ignore-next-line */
                                                    if ($player->isConnected()) {
                                                        $player->sendMessage($message);
                                                    }
                                                });
                                            }
                                        });
                                    } elseif (($maxFriends = $this->getMaxFriends($p)) !== -1 && count($this->getFriends($p)) >= $maxFriends) {
                                        $player->sendMessage('§cThat player can\'t add any more friends.');
                                    } else {
                                        $acceptCallable($player);
                                    }
                                }
                            }));
                            $form->setButton2(new Button(TextFormat::RED . 'No', function (Player $player) use ($requester) {
                                $this->removeRelation($player->getName(), $requester);

                                $player->sendMessage('§6Declined the friend request from §b' . $requester . '§6.');

                                $chatManager = $this->getSocialManager()->getManager()->getChatManager();
                                $chatManager->sendGuaranteedMessage($requester, new ChatText(new RawMessage(self::PREFIX . '§b' . $player->getName() . ' §6has declined your friend request.')));
                            }));

                            $form->sendForm();
                        }
                    }));
                }
            } else {
                $form->setContent("You don't have any friend requests.");
            }

            $form->addButton(new Button(TextFormat::GOLD . 'Disable Friend Requests', function (Player $player) use ($onBack) {
                $this->getSocialManager()->getPlugin()->getPlayerData()->setValue($player, PlayerData::FRIEND_REQUESTS, false);
                Translator::sendMessage($player, "formhandler.settings.friends", Translator::TYPE_SUCCESS, ...["enabledOrDisabled" => Translator::getTranslationPlayer($player, "formhandler.settings.hiding")]);

                if ($onBack !== null) {
                    $onBack($player);
                }
            }));

            $form->sendForm();
        }
    }

    public function setRelation(string $player_one, string $player_two, int $status, bool $broadcast = true): void
    {
        $plugin = $this->getSocialManager()->getPlugin();

        $shouldBroadcast = 2;

        if ($status === self::RELATION_REQUEST) {
            $shouldBroadcast--;
        } else if (($p_one = $plugin->getServer()->getPlayerExact($player_one)) !== null) {
            $relation = $plugin->getPlayerData()->getArray($p_one, PlayerData::RELATIONS);
            $relation[$player_two] = $status;
            $plugin->getPlayerData()->setValue($p_one, PlayerData::RELATIONS, $relation);

            $shouldBroadcast--;
        }

        if (($p_two = $plugin->getServer()->getPlayerExact($player_two)) !== null) {
            $relation = $plugin->getPlayerData()->getArray($p_two, PlayerData::RELATIONS);
            $relation[$player_one] = $status;
            $plugin->getPlayerData()->setValue($p_two, PlayerData::RELATIONS, $relation);

            $shouldBroadcast--;
        }

        if ($broadcast) {
            MySQLCredentials::executeInsert('player_relations.set', ['player_one' => $player_one, 'player_two' => $player_two, 'status' => $status], function (int $insertId, int $affectedRows) use ($plugin, $shouldBroadcast, $status, $player_one, $player_two): void {
                if ($plugin->getServerManager()->getServerType() === ServerManager::SB) {
                    $this->updateIslandMemberData($player_one, $player_two);
                }

                if ($shouldBroadcast > 0) {
                    $plugin->getPublisher()->publishMessage(self::FRIENDS_TOPIC, (string)$status, implode(':', [$player_one, $player_two]));
                }
            });
        } else if ($plugin->getServerManager()->getServerType() === ServerManager::SB) {
            $this->updateIslandMemberData($player_one, $player_two);
        }
    }

    public function sendAddFriendMenu(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Add Friend');

            $playerManager = $this->getSocialManager()->getManager();
            $playersNames = $playerManager->getPlayerNames(array_diff($player->getServer()->getOnlinePlayers(), [$player], $this->getFriends($player)));

            $form->addElement(new Dropdown('Select a player:', $playersNames, -1, function (Player $player, int $value) use ($playerManager, $playersNames) {
                $playerInvited = $playerManager->getBestMatchingPlayer($playersNames[$value]);

                if ($playerInvited instanceof Player) {
                    $this->sendInvite($player, $playerInvited);
                } else {
                    Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                }
            }));

            $form->sendForm();
        }
    }

    public function sendInvite(Player $inviter, Player $invited): void
    {
        if (($maxFriends = $this->getMaxFriends($inviter)) !== -1 && count($this->getFriends($inviter)) >= $maxFriends) {
            $inviter->sendMessage("§cYou don't have permission to add more friends! Buy a rank at §bngmc.co/store §cto add more!");
        } elseif (($maxFriends = $this->getMaxFriends($invited)) !== -1 && count($this->getFriends($invited)) >= $maxFriends) {
            $inviter->sendMessage('§cThat player can\'t add any more friends.');
        } elseif ($this->hasRequested($inviter, $invited)) {
            $inviter->sendMessage('§cYou\'ve already sent a friend request to §b' . $invited->getName() . '§c.');
        } elseif (!$this->getSocialManager()->getPlugin()->getPlayerData()->getBool($invited, PlayerData::FRIEND_REQUESTS)) {
            $inviter->sendMessage('§b' . $invited->getName() . ' §chas blocked friend requests. Ask them to allow friend requests to add them as a friend.');
        } else {
            $this->setRelation($inviter->getName(), $invited->getName(), self::RELATION_REQUEST);
            $inviter->sendMessage('§aSent a friend request to §b' . $invited->getName() . '§a.');
            $invited->sendMessage('§b' . $inviter->getName() . ' §6has requested to be your friend! Use the Social Menu to accept the request.');
        }
    }

    public function hasRequested(Player $player, Player $invited): bool
    {
        if (count($invites = $this->getRequests($invited)) !== 0) {
            return in_array($player->getName(), $invites, true);
        }

        return false;
    }

    public function sendFriendMessage(Player $player, string $message): void
    {
        $globalChatManager = $this->getSocialManager()->getManager()->getChatManager()->getGlobalChatManager();
        $text = new ChatText(new RawMessage(self::PREFIX . $message));
        $globalChatManager->sendPrivateMessage($text, $this->getFriends($player));
    }

    /**
     * Check if the player is invited or not
     *
     * @param Player $invited
     *
     * @return bool
     */
    public function isRequested(Player $invited): bool
    {
        return count($this->getRequests($invited)) !== 0;
    }

    public function updatePlayerName(string $newPlayerName, string $oldPlayerName): void
    {
        MySQLCredentials::executeChange('player_relations.update1', ['old_player' => $oldPlayerName, 'new_player' => $newPlayerName]);
        MySQLCredentials::executeChange('player_relations.update2', ['old_player' => $oldPlayerName, 'new_player' => $newPlayerName]);
    }

    public function isFriend(Player $player, string $playerName): bool
    {
        return in_array($playerName, $this->getFriends($player));
    }

    public function loadRelations(string $playerName, callable $callable, bool $store = true): void
    {
        MySQLCredentials::executeSelect('player_relations.get', ['player' => $playerName], function (array $rows) use ($playerName, $callable, $store) {
            $relations = [];

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $status = (int)$row['status'];
                    if ($row['player_one'] === $playerName) {
                        if ($status !== self::RELATION_REQUEST) { // don't add own requests
                            $relations[$row['player_two']] = $status;
                        }
                    } else {
                        $relations[$row['player_one']] = $status;
                    }
                }
            }

            if ($store) {
                $this->getSocialManager()->getPlugin()->getPlayerData()->setValue($playerName, PlayerData::RELATIONS, $relations);
            }

            $callable($relations);
        }, static function (SqlError $error) use ($callable): void {
            $callable([]);
        });
    }
}