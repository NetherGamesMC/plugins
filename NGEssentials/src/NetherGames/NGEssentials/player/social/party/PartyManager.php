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

namespace NetherGames\NGEssentials\player\social\party;

use libforms\elements\Button;
use libforms\elements\Dropdown;
use libforms\elements\ImageButton;
use libforms\elements\Toggle;
use libforms\FormManager;
use libminigames\Minigame;
use NetherGames\NGEssentials\kafka\KafkaServerTopic;
use NetherGames\NGEssentials\player\chat\types\PartyChat;
use NetherGames\NGEssentials\player\GameSettings;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\party\objects\Party;
use NetherGames\NGEssentials\player\social\SocialManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Server;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_key_last;
use function array_rand;
use function array_reduce;
use function count;
use function in_array;

class PartyManager
{
    /** @var Party[] */
    private array $parties = [];
    /** @var string[][] */
    private array $invites = [];
    /** @var KafkaServerTopic */
    private KafkaServerTopic $partyTransferTopic;

    public function __construct(private SocialManager $socialManager)
    {
        $this->partyTransferTopic = new KafkaServerTopic('party_transfer', $this->socialManager->getPlugin()->getServerManager(), function (string $key, string $message): void {
            $party = Party::fromString($message);

            if ($party !== null) {
                $this->addParty($party);
            }
        });
    }

    private function canHavePublicParty(Player $player): bool
    {
        return $player->hasPermission(Permissions::RANK_TITAN) || $player->hasPermission(Permissions::RANK_YOUTUBE) || $player->hasPermission(Permissions::RANK_MEDIA);
    }

    private function canHavePrivateGames(Player $player): bool
    {
        return $player->hasPermission(Permissions::RANK_LEGEND);
    }

    public function sendPartiesMenu(Player $player, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $invites = $this->getInvites($player);
            $party = $this->getParty($player);

            $form->setTitle('Parties');
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendPartiesMenu($player, $onBack);
            };

            if ($party === null) {
                $form->addButton(new Button(TextFormat::YELLOW . 'Create a party', function (Player $player) use ($goBack): void {
                    $this->createParty($player);
                    $goBack($player);
                }));

                $publicParties = $this->getPublicParties();
                if (count($publicParties) !== 0) {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Public Parties' . TextFormat::EOL . TextFormat::GRAY . 'Available Parties: ' . count($publicParties), function (Player $player) use ($goBack, $publicParties): void {
                        $form = FormManager::createSimpleForm($player);

                        if ($form !== null) {
                            $form->setTitle('Public Parties');
                            $form->setBackClosure($goBack);

                            foreach ($publicParties as $party) {
                                $form->addButton(new Button(TextFormat::YELLOW . $party->getLeaderName() . "'s Party" . TextFormat::EOL . TextFormat::GRAY . 'Join ' . $party->getLeaderName() . "'s party", function (Player $player) use ($goBack, $party): void {
                                    $host = $party->getLeader();

                                    if ($host === null) {
                                        $player->sendMessage(TextFormat::RED . 'That party is no longer on this server.');
                                    } elseif ($party->getTotalMembers() >= $this->getMaxPartySize($host)) {
                                        $player->sendMessage('§cThat party is currently full!');
                                    } elseif ($party->addMember($player->getName())) {
                                        Translator::sendMessage($player, "party.welcome", Translator::TYPE_SUCCESS, ...["host" => $host->getName()]);
                                        $this->sendPartyMessage($party, TextFormat::AQUA . $player->getName() . ' §6joined the party.', [$player]);

                                        $goBack($player);
                                    }
                                }));
                            }

                            $form->sendForm();
                        }
                    }));
                }

                $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
                if ($playerData->getBool($player, PlayerData::PARTY_REQUESTS)) {
                    $form->addButton(new Button(TextFormat::YELLOW . 'View invites' . TextFormat::EOL . TextFormat::GRAY . 'Current invites: ' . count($invites), function (Player $player) use ($goBack, $invites, $playerData): void {
                        $form = FormManager::createSimpleForm($player);

                        if ($form !== null) {
                            $form->setTitle('Party Invites');
                            $form->setBackClosure($goBack);

                            foreach ($invites as $invite) {
                                $form->addButton(new Button(TextFormat::YELLOW . $invite . "'s party" . TextFormat::EOL . TextFormat::GRAY . 'Join ' . $invite . "'s party.", function (Player $player) use ($goBack, $invite): void {
                                    $host = $player->getServer()->getPlayerExact($invite);

                                    if ($host === null) {
                                        $player->sendMessage(TextFormat::RED . 'That party is no longer on this server.');
                                    } else {
                                        $party = $this->getParty($host);

                                        if ($party === null) {
                                            $player->sendMessage(TextFormat::RED . 'That party is no longer on this server.');
                                            $this->removeInvite($player->getName(), $invite);
                                        } else {
                                            $this->removeInvites($player->getName());

                                            if ($party->getTotalMembers() >= $this->getMaxPartySize($host)) {
                                                $player->sendMessage('§cThat party is currently full!');
                                            } elseif ($party->addMember($player->getName())) {
                                                Translator::sendMessage($player, "party.welcome", Translator::TYPE_SUCCESS, ...["host" => $host->getName()]);
                                                $this->sendPartyMessage($party, TextFormat::AQUA . $player->getName() . ' §6joined the party.', [$player]);

                                                $goBack($player);
                                            }
                                        }
                                    }
                                }));
                            }

                            $form->addButton(new Button(TextFormat::RED . 'Disable invites' . TextFormat::EOL . TextFormat::GRAY . 'Click to disable.', function (Player $player) use ($goBack, $playerData): void {
                                $playerData->setValue($player, PlayerData::PARTY_REQUESTS, false);
                                $goBack($player);
                                $this->removeInvites($player->getName());
                            }));

                            $form->sendForm();
                        }
                    }));
                } else {
                    $form->addButton(new Button(TextFormat::RED . 'Invites Disabled' . TextFormat::EOL . TextFormat::GRAY . 'Click to enable.', function (Player $player) use ($goBack, $playerData): void {
                        $playerData->setValue($player, PlayerData::PARTY_REQUESTS, true);
                        $goBack($player);
                    }));
                }
            } else {
                $partySize = $party->getTotalMembers();
                $maxSize = $this->getMaxPartySize($party->getLeader());

                $form->addButton(new Button(TextFormat::YELLOW . 'Party members' . TextFormat::EOL . TextFormat::GRAY . 'Members: ' . TextFormat::GREEN . $partySize . '/' . $maxSize, function (Player $player) use ($goBack, $party): void {
                    $this->sendMembersMenu($player, $party, $goBack);
                }));

                if ($party->getLeader() === $player) {
                    if ($partySize < $maxSize) {
                        $form->addButton(new Button(TextFormat::YELLOW . 'Invite players', function (Player $player) use ($goBack, $party) {
                            $form = FormManager::createCustomForm($player, $goBack);

                            if ($form !== null) {
                                $form->setTitle('Invite players');

                                $playerManager = $this->getSocialManager()->getManager();
                                $playersNames = $playerManager->getPlayerNames(array_diff($player->getServer()->getOnlinePlayers(), $this->getPlayers($party)));

                                $form->addElement(new Dropdown(Translator::getTranslationPlayer($player, "forms.party.invite"), $playersNames, -1, function (Player $player, int $value) use ($playerManager, $playersNames) {
                                    $playerInvited = $playerManager->getBestMatchingPlayer($playersNames[$value]);

                                    if ($playerInvited instanceof Player) {
                                        $this->invitePlayer($player, $playerInvited);
                                    } else {
                                        Translator::sendMessage($player, "player.offline", Translator::TYPE_ERROR);
                                    }
                                }));

                                $form->sendForm();
                            }
                        }));
                    } else {
                        $form->addButton(new Button(TextFormat::RED . 'FULL', $goBack));
                    }


                    $form->addButton(new Button(TextFormat::YELLOW . 'Settings', function (Player $player) use ($goBack, $party): void {
                        $form = FormManager::createCustomForm($player, $goBack);

                        if ($form !== null) {
                            $form->setTitle('Settings');

                            if ($this->canHavePublicParty($player)) {
                                $form->addElement(new Toggle('Public Party', $party->isPublic(), static function (Player $player, bool $public) use ($party): void {
                                    if ($public) {
                                        $player->sendMessage('§aEnabled public party. All players on the same server as you will now be able to join your party.');
                                    } else {
                                        $player->sendMessage('§cDisabled public party. You will now need to invite players to join your party.');
                                    }

                                    $party->setPublic($public);
                                }));
                            }

                            if ($this->canHavePrivateGames($player)) {
                                $form->addElement(new Toggle('Private Games', $party->hasPrivateGames(), static function (Player $player, bool $privateGames) use ($party): void {
                                    if ($privateGames) {
                                        $player->sendMessage('§aEnabled private games. Your party will now be queued into a separate match without any other players upon joining a game.');
                                    } else {
                                        $player->sendMessage('§cDisabled private games.');
                                    }

                                    $party->setPrivateGames($privateGames);
                                }));
                            }
                            $form->addElement(new Toggle('Player Randomisation', $party->hasPlayerRandomization(), static function (Player $player, bool $randomization) use ($party): void {
                                if ($randomization) {
                                    $player->sendMessage('§aEnabled player randomisation. Players will be put into random teams in your match.');
                                } else {
                                    $player->sendMessage('§cDisabled player randomisation. Players will no longer be put into random teams.');
                                }

                                $party->setPlayerRandomization($randomization);
                            }));

                            $form->sendForm();
                        }
                    }));

                    $form->addButton(new Button(TextFormat::YELLOW . 'Disband the party', function (Player $player) use ($goBack, $party) {
                        $form = FormManager::createModalForm($player);

                        if ($form !== null) {
                            $form->setTitle('Disband Party');

                            $form->setContent('Are you sure you want to disband your party?');

                            $form->setButton1(new Button(TextFormat::GREEN . 'Yes', function (Player $player) use ($party) {
                                $this->leaveParty($party, $player, true);
                            }));
                            $form->setButton2(new Button(TextFormat::RED . 'No', $goBack));

                            $form->sendForm();
                        }
                    }));
                } else {
                    $form->addButton(new Button(TextFormat::YELLOW . 'Leave party', function (Player $player) use ($goBack, $party) {
                        $form = FormManager::createModalForm($player);

                        if ($form !== null) {
                            $form->setTitle('Leave Party');

                            $form->setContent('Are you sure you want to leave ' . $party->getLeaderName() . '\'s party?');

                            $form->setButton1(new Button(TextFormat::GREEN . 'Yes', function (Player $player) use ($party) {
                                $this->leaveParty($party, $player);
                            }));
                            $form->setButton2(new Button(TextFormat::RED . 'No', $goBack));

                            $form->sendForm();
                        }

                    }));
                }
            }

            $form->sendForm();
        }
    }

    /**
     * Return the name of player that invite $invited
     *
     * @param Player $invited
     *
     * @return string[]
     */
    public function getInvites(Player $invited): array
    {
        return $this->invites[$invited->getName()] ?? [];
    }

    public function getParty(Player $player, bool $allowDisconnect = true): ?Party
    {
        if ($this->isPartyCreator($player)) {
            return $this->parties[$player->getName()];
        }

        foreach ($this->getParties() as $party) {
            if (in_array($player->getName(), $party->getMembers(), true)) {
                if ($allowDisconnect && (($leader = $party->getLeader()) === null || !$leader->isConnected())) {
                    return $this->findNewLeader($party);
                }

                return $party;
            }
        }

        return null;
    }

    public function isPartyCreator(Player $player): bool
    {
        return isset($this->parties[$player->getName()]);
    }

    /**
     * @return Party[]
     */
    public function getParties(): array
    {
        return $this->parties;
    }

    /**
     * Tries to find a new leader for a party when the old one disconnected
     *
     * @param Party $party
     * @return Party|null
     */
    public function findNewLeader(Party $party): ?Party
    {
        $members = $this->getPlayers($party);

        if (count($members) > 0) {
            $this->setLeader($party, $members[array_rand($members)], false);
            return $party;
        }

        $this->disconnectParty($party);
        return null;
    }

    /**
     * Return an array of Player in a party
     *
     * @param Party $party
     *
     * @return Player[]
     */
    public function getPlayers(Party $party): array
    {
        $server = $this->getSocialManager()->getPlugin()->getServer();

        return array_reduce(
            $party->getAll(),
            function (array $players, string $playerName) use ($server) {
                if (($player = $server->getPlayerExact($playerName)) !== null && $player->isConnected()) {
                    $players[] = $player;
                }

                return $players;
            },
            []
        );
    }

    /**
     * @return SocialManager
     */
    public function getSocialManager(): SocialManager
    {
        return $this->socialManager;
    }

    /**
     * Make a player of the party leader
     *
     * @param Party $party
     * @param Player $leader
     */
    public function setLeader(Party $party, Player $leader, bool $addOldLeader = true): void
    {
        $this->wipeInvites($party);
        $this->removeParty($party);

        if ($addOldLeader) {
            $party->addMember($party->getLeaderName());
        }
        $party->setLeader($leader);
        $party->removeMember($leader->getName());

        if ($party->isPublic() && !$this->canHavePublicParty($leader)) {
            $party->setPublic(false);
        }

        if ($party->hasPrivateGames() && !$this->canHavePrivateGames($leader)) {
            $party->setPrivateGames(false);
        }

        $this->addParty($party);
        $this->sendPartyMessage($party, '§6' . $leader->getName() . ' §ahas been promoted to party leader.');
    }

    public function wipeInvites(Party $party): void
    {
        $leaderName = $party->getLeaderName();

        foreach ($this->invites as $player => $parties) {
            if (in_array($leaderName, $parties, true)) {
                $this->removeInvite($player, $leaderName);
            }
        }
    }

    public function removeInvite(string $invited, string $party): void
    {
        if (isset($this->invites[$invited])) {
            $this->invites[$invited] = array_diff($this->invites[$invited], [$party]);

            if (count($this->invites[$invited]) === 0) {
                $this->removeInvites($invited);
            }
        }
    }

    public function removeInvites(string $invited): void
    {
        unset($this->invites[$invited]);
    }

    /**
     * @param Party $party
     * @internal
     *
     */
    public function removeParty(Party $party): void
    {
        unset($this->parties[$party->getLeaderName()]);
    }

    public function addParty(Party $party): void
    {
        $this->parties[$party->getLeaderName()] = $party;
    }

    /**
     * Send a message to all party members
     *
     * @param Party $party
     * @param string $message
     * @param array $exclude
     */
    public function sendPartyMessage(Party $party, string $message, array $exclude = []): void
    {
        $plugin = $this->getSocialManager()->getPlugin();
        $players = $this->getPlayers($party);

        $plugin->getServer()->broadcastMessage(PartyChat::PREFIX . $message, array_diff($players, $exclude));
    }

    /**
     * Forcefully leave a party
     *
     * @param Party $party
     */
    private function disconnectParty(Party $party): void
    {
        $this->wipeInvites($party);
        $this->removeParty($party);
    }

    public function createParty(Player $player): Party
    {
        $party = new Party($player->getName());
        $this->addParty($party);

        return $party;
    }

    /**
     * @return Party[]
     */
    public function getPublicParties(): array
    {
        $publicParties = [];

        foreach ($this->getParties() as $party) {
            if ($party->isPublic()) {
                $publicParties[] = $party;
            }
        }

        return $publicParties;
    }

    /**
     * Return the maximum size of the players in a party
     *
     * @param Player $host
     *
     * @return int
     */
    public function getMaxPartySize(Player $host): int
    {
        if ($host->hasPermission(Permissions::RANK_TITAN) || $host->hasPermission(Permissions::RANK_YOUTUBE)) {
            return 40;
        }

        if ($host->hasPermission(Permissions::RANK_LEGEND)) {
            return 12;
        }

        if ($host->hasPermission(Permissions::RANK_EMERALD)) {
            return 10;
        }

        if ($host->hasPermission(Permissions::RANK_ULTRA)) {
            return 8;
        }

        return 5;
    }

    public function sendMembersMenu(Player $player, Party $party, ?callable $onBack = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $members = array_diff($this->getPlayers($party), [$party->getLeader()]);

            $form->setTitle('Party Members');
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack, $party) {
                $this->sendMembersMenu($player, $party, $onBack);
            };

            $form->addButton(new ImageButton(TextFormat::YELLOW . $party->getLeaderName() . TextFormat::EOL . TextFormat::GRAY . 'Party leader', ImageButton::IMAGE_TYPE_FACE, $party->getLeaderName(), $goBack));

            foreach ($members as $member) {
                $form->addButton(new ImageButton(TextFormat::YELLOW . $member->getName(), ImageButton::IMAGE_TYPE_FACE, $member->getName(), function (Player $player) use ($goBack, $party, $member) {
                    if ($party->getLeader() === $player) {
                        $form = FormManager::createSimpleForm($player);

                        if ($form !== null) {
                            $form->setTitle('Party Member: ' . $member->getName());
                            $form->setBackClosure($goBack);

                            $form->addButton(new Button(TextFormat::YELLOW . 'Promote' . TextFormat::EOL . TextFormat::GRAY . 'Make them the party leader.', function (Player $player) use ($party, $member) {
                                if ($member->isConnected()) {
                                    $this->setLeader($party, $member);
                                }
                            }));
                            $form->addButton(new Button(TextFormat::YELLOW . 'Kick' . TextFormat::EOL . TextFormat::GRAY . 'Remove them from the party.', function (Player $player) use ($party, $member) {
                                if ($member->isConnected()) {
                                    $party->removeMember($member->getName());
                                    Translator::sendMessage($member, "party.kicked", Translator::TYPE_ERROR, ...["kicker" => $player->getName()]);
                                    $this->sendPartyMessage($party, Translator::getTranslation(Translator::FALLBACK_LANGUAGE, "party.kicked.message", Translator::TYPE_INFO, ...["kicked" => $member->getName(), "kicker" => $player->getName()]));
                                }
                            }));

                            $form->sendForm();
                        }
                    }
                }));
            }

            $form->sendForm();
        }
    }

    /**
     * Invite $invited into $sender's party
     *
     * @param Player $host
     * @param Player $invited
     */
    public function invitePlayer(Player $host, Player $invited): void
    {
        if (!$this->getSocialManager()->getPlugin()->getPlayerData()->getBool($invited, PlayerData::PARTY_REQUESTS)) {
            $host->sendMessage(TextFormat::RED . "That player is not accepting party invites.");
            return;
        }

        if ($isHost = $this->isPartyCreator($host)) {
            $isInParty = true;
        } else {
            $isInParty = $this->isInParty($host);
        }

        if ($isInParty) {
            if ($isHost) {
                if ($this->isInParty($invited)) {
                    Translator::sendMessage($host, "party.already.in.host", Translator::TYPE_ERROR, ...["invited" => $invited->getName()]);
                } elseif ($this->isInvitedByHost($host, $invited)) {
                    Translator::sendMessage($host, "party.already.invited", Translator::TYPE_ERROR, ...["invited" => $invited->getName()]);
                } elseif (($party = $this->getParty($host)) !== null && $party->getTotalMembers() >= $this->getMaxPartySize($host)) {
                    Translator::sendMessage($host, "party.max", Translator::TYPE_ERROR, ...["max" => (string)$this->getMaxPartySize($host)]);
                } else {
                    $this->invites[$invited->getName()][] = $host->getName();

                    Translator::sendMessage($host, "party.invited", Translator::TYPE_SUCCESS, ...["invited" => $invited->getName()]);
                    $invited->sendToastNotification("§eNew Party Invite", "§e" . $host->getName() . " has invited you to their party.");
                }
            } else {
                $host->sendMessage("§cYou're not the party host!");
            }
        } else {
            $this->createParty($host);
            $this->invitePlayer($host, $invited);
        }
    }

    /**
     * Check if a player is in a party
     *
     * @param Player $player
     *
     * @return bool
     */
    public function isInParty(Player $player): bool
    {
        return $this->getParty($player) !== null;
    }

    /**
     * Check if player is invited by a specific host
     *
     * @param Player $host
     * @param Player $invited
     *
     * @return bool
     */
    public function isInvitedByHost(Player $host, Player $invited): bool
    {
        if ($this->isInvited($invited)) {
            return in_array($host->getName(), $this->getInvites($invited), true);
        }

        return false;
    }

    /**
     * Check if the player is invited or not
     *
     * @param Player $invited
     *
     * @return bool
     */
    public function isInvited(Player $invited): bool
    {
        return isset($this->invites[$invited->getName()]);
    }

    /**
     * Leave a party
     *
     * @param Party $party
     * @param Player $player
     */
    public function leaveParty(Party $party, Player $player, bool $endParty = false): void
    {
        if ($party->getLeader() === $player) {
            if (!$endParty && $party->getTotalMembers() === 2) {
                $endParty = true;
            }

            if ($endParty || ($this->findNewLeader($party) === null)) {
                Translator::sendMessage($player, "party.ended.host", Translator::TYPE_SUCCESS);
                $this->sendPartyMessage($party, '§b' . $player->getName() . "'s §6party has ended. Thanks for playing!", [$player]);

                if ($endParty) {
                    $this->disconnectParty($party);
                }
            }
        } else {
            Translator::sendMessage($player, "party.leave.player", Translator::TYPE_SUCCESS);
            $party->removeMember($player->getName());
            $this->sendPartyMessage($party, '§b' . $player->getName() . ' §6left the party.');
        }
    }

    public function cleanMembers(Party $party): void
    {
        $server = $this->getSocialManager()->getPlugin()->getServer();

        foreach ($party->getMembers() as $memberName) {
            if (($player = $server->getPlayerExact($memberName)) === null || !$player->isConnected()) {
                $party->removeMember($memberName);
            }
        }
    }

    public function transferParty(Player $player, Server $server): bool
    {
        /** @var NGPlayer $player */
        if ($this->isPartyCreator($player) && ($party = $this->getParty($player)) !== null) {
            $players = array_diff($this->getPlayers($party), [$player]);
            $plugin = $this->getSocialManager()->getPlugin();
            $playerData = $plugin->getPlayerData();
            $playerManager = $plugin->getPlayerManager();

            foreach ($players as $p) {
                if ($playerManager->isInArena($p)) {
                    Translator::sendMessage($player, "party.still.in.game", Translator::TYPE_ERROR, ...["member" => $p->getName()]);
                    return false;
                }

                if ($playerData->getBool($p, PlayerData::TRACK)) {
                    $player->sendMessage(TextFormat::RED . $p->getName() . ' is tracking someone. Please wait for them to leave.');
                    return false;
                }
            }

            $server->getCluster()->removeFromQueue($party->getLeader());
            $this->forceTransfer($party, $server);

            return true;
        }

        Translator::sendMessage($player, "party.join.game", Translator::TYPE_ERROR);
        return false;
    }

    public function forceTransfer(Party $party, ?Server $server = null): void
    {
        $plugin = $this->getSocialManager()->getPlugin();

        $this->removeParty($party);

        $leader = $party->getLeader();
        $serverManager = $plugin->getServerManager();
        if ($leader instanceof NGPlayer) {
            if ($server === null) {
                $cluster = $serverManager->getCluster(ServerManager::LOBBY);

                $playerData = $plugin->getPlayerData();
                $players = $this->getPlayers($party);
                foreach ($players as $p) {
                    $playerData->setValue($p, PlayerData::PRE_TRANSFER, true);
                }

                $serverManager->findBestServer($leader, $cluster, true, function (Server $server) use ($party) {
                    $this->forceTransfer($party, $server);
                }, function () use ($playerData, $players): void {
                    foreach ($players as $p) {
                        $playerData->unsetValue($p, PlayerData::PRE_TRANSFER);
                    }
                });
            } else {
                $this->partyTransferTopic->send(
                    $server,
                    '',
                    $party->toString(),
                    function () use ($party, $server): void {
                        foreach ($this->getPlayers($party) as $p) {
                            $this->getSocialManager()->getManager()->forceTransfer($p, $server);
                        }
                    }
                );
            }
        } else {
            foreach ($this->getPlayers($party) as $p) {
                $plugin->getPlayerManager()->forceTransfer($p, $server);
            }
        }
    }

    public function getQueuingRegion(Party $party): string
    {
        $regions = [
            ServerManager::REGION_AP => 0,
            ServerManager::REGION_EU => 0,
            ServerManager::REGION_US => 0,
            ServerManager::REGION_IND => 0,
        ];

        foreach ($this->getPlayers($party) as $player) {
            /** @var NGPlayer $player */
            $regions[$player->getProxyRegion()]++;
        }

        asort($regions);

        return array_key_last($regions);
    }

    public function getQueuingMode(Party $party): string
    {
        $playerData = $this->getSocialManager()->getPlugin()->getPlayerData();
        $queuingMode = Minigame::QUEUING_PREFER_MOBILE;

        foreach ($this->getPlayers($party) as $member) {
            /** @var NGPlayer $member */
            if ($member->getInputMode() !== InputMode::TOUCHSCREEN) {
                return Minigame::QUEUING_GLOBAL;
            }

            if ($playerData->getGameSettings()->getBool($member, GameSettings::TOUCH_ONLY)) {
                $queuingMode = Minigame::QUEUING_FORCE_MOBILE;
            }
        }

        return $queuingMode;
    }
}