<?php
/*
 *   _____           _                            _   _      _                _
 *  |  __ \         | |               /\         | | (_)    | |              | |
 *  | |__) |__ _  __| | ___  _ __    /  \   _ __ | |_ _  ___| |__   ___  __ _| |_
 *  |  _  // _` |/ _` |/ _ \| '_ \  / /\ \ | '_ \| __| |/ __| '_ \ / _ \/ _` | __|
 *  | | \ \ (_| | (_| | (_) | | | |/ ____ \| | | | |_| | (__| | | |  __/ (_| | |_
 *  |_|  \_\__,_|\__,_|\___/|_| |_/_/    \_\_| |_|\__|_|\___|_| |_|\___|\__,_|\__|
 *
 *  Copyright (C) 2020-2026 NetherGames Network
 *
 *  This is private software, you cannot redistribute and/or modify it in any way
 *  unless given explicit permission to do so. If you have not been given explicit
 *  permission to view or modify this software you should take the appropriate actions
 *  to remove this software from your device immediately.
 *
 *  @author noah, Driesboy, larryTheCoder
 */

namespace NetherGames\NGEssentials\player\social\guilds\objects;

use Closure;
use Generator;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\social\guilds\GuildManager;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function implode;
use function is_array;
use function ucfirst;

class Guild
{
    public const RANK_REMOVED = -1;
    public const RANK_MEMBER = 0;
    public const RANK_OFFICER = 1;
    public const RANK_LEADER = 2;

    public const ADD_MEMBER_LOCKED = 0;
    public const ADD_MEMBER_FULL = 1;
    public const ADD_MEMBER_EXISTS = 2;
    public const ADD_MEMBER_OK = 3;
    public const ADD_MEMBER_ERROR = 4;

    public const RENAME_GUILD_OK = 0;
    public const RENAME_GUILD_ERROR = 1;
    public const RENAME_GUILD_EXISTS = 2;

    private bool $isOperationLocked = false;
    private bool $xpOperationLocked = false;

    public function __construct(
        private GuildManager $manager,
        private int          $guildId,
        private string       $guildName,
        private string       $leader,
        private int          $maxGuildSize = 50, // Database override this
        private string       $motd = '',
        private int          $xp = 0,
        private string       $tag = '',
        private bool         $disabled = false,
        private array        $officers = [],
        private array        $members = [])
    {
        $this->members[] = $leader;
    }

    public function isMember(string $playerName): bool
    {
        return in_array($playerName, $this->getMembers());
    }

    /**
     * @return string[]
     */
    public function getMembers(bool $strict = false): array
    {
        return $strict ? array_diff($this->members, array_merge($this->getOfficers(), [$this->getLeader()])) : $this->members;
    }

    /**
     * @return string[]
     */
    public function getOfficers(): array
    {
        return $this->officers;
    }

    /**
     * @return string
     */
    public function getLeader(): string
    {
        return $this->leader;
    }

    public function isOfficer(string $playerName): bool
    {
        return in_array($playerName, $this->getOfficers());
    }

    public function getMemberByPrefix(string $playerName): ?string
    {
        $found = null;
        $name = strtolower($playerName);
        $delta = PHP_INT_MAX;
        foreach ($this->getMembers() as $player) {
            if (stripos($player, $name) === 0) {
                $curDelta = strlen($player) - strlen($name);
                if ($curDelta < $delta) {
                    $found = $player;
                    $delta = $curDelta;
                }
                if ($curDelta === 0) {
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * @param Player|string $sender
     * @return int
     */
    public function getGuildRole(Player|string $sender): int
    {
        if ($sender instanceof Player) {
            $sender = $sender->getName();
        }

        if ($this->leader === $sender) {
            return self::RANK_LEADER;
        }

        if (in_array($sender, $this->officers, true)) {
            return self::RANK_OFFICER;
        }

        return self::RANK_MEMBER;
    }

    // ----------------------------------------------- GUILD OPERATIONS ------------------------------------------------

    public function renameGuildName(string $newGuildName, bool $pushUpdates = false, ?Closure $onComplete = null): void
    {
        Await::f2c(function () use ($newGuildName, $pushUpdates): Generator {
            if ($pushUpdates) {
                MySQLCredentials::executeSelect("guild.exists", [
                    'guild_name' => $newGuildName
                ], yield, yield Await::REJECT);

                $rows = yield Await::ONCE;

                if (count($rows) > 0) {
                    return self::RENAME_GUILD_EXISTS;
                }

                MySQLCredentials::executeChange("guild.rename", [
                    'guild_name' => $newGuildName,
                    'guild_id' => $this->getGuildId()
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_GUILD_NAME, [$newGuildName]);
            }

            $this->guildName = $newGuildName;

            return self::RENAME_GUILD_OK;
        }, $onComplete, catches: function (Throwable $error) use ($onComplete) {
            $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);

            $onComplete(self::RENAME_GUILD_ERROR);
        });
    }

    /**
     * @return int
     */
    public function getGuildId(): int
    {
        return $this->guildId;
    }

    /**
     * @return GuildManager
     */
    public function getManager(): GuildManager
    {
        return $this->manager;
    }

    public function setLeader(Player|string $member, bool $pushUpdate = false): void
    {
        if ($member instanceof Player) {
            $member = $member->getName();
        }
        $currentLeader = $this->leader;

        if (($key = array_search($member, $this->officers, true)) !== false) {
            unset($this->officers[$key]);
        }

        $this->leader = $member;

        $this->officers[] = $currentLeader;

        if ($pushUpdate) {
            Await::f2c(function () use ($member, $currentLeader) {
                MySQLCredentials::executeInsert("guild.update_leader", [
                    'guild_id' => $this->getGuildId(),
                    'old_leader' => $currentLeader,
                    'new_leader' => $member,
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_LEADER, [$member]);
            }, catches: function (Throwable $error) {
                $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);
            });
        }
    }

    public function updatePlayerName(string $playerName, string $oldName, bool $update = false): void
    {
        if ($update) {
            $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_UPDATE_PLAYER_NAME, [$oldName, $playerName]);
        }

        // Check if the player's old name exists in this server, if not, we don't do anything
        // This case usually been checked if the guild is freshly loaded in the server without any references.
        if (($key = array_search($oldName, $this->members, true)) === false) {
            return;
        }

        $this->members[$key] = $playerName;
        if (($key = array_search($oldName, $this->officers, true)) !== false) {
            $this->officers[$key] = $playerName;
        } else if ($this->leader === $oldName) {
            $this->leader = $playerName;
        }
    }

    public function setMemberRole(Player|string $member, int $role = self::RANK_MEMBER, bool $pushUpdate = false): void
    {
        if ($member instanceof Player) {
            $member = $member->getName();
        }

        if ($member === $this->getLeader() || $role === self::RANK_LEADER) {
            throw new RuntimeException('Cannot change a member to leader directly, use the right function to change the guild leadership.');
        } else if ($role === self::RANK_MEMBER) {
            if (($key = array_search($member, $this->officers, true)) === false) {
                return;
            }

            unset($this->officers[$key]);
        } else if ($role === self::RANK_OFFICER) {
            if (in_array($member, $this->officers, true) !== false) {
                return;
            }

            $this->officers[] = $member;
        } else if ($role === self::RANK_REMOVED) {
            $this->removeMember($member, $pushUpdate);

            return;
        } else {
            throw new RuntimeException('Unknown role given, please check again.');
        }

        if ($pushUpdate) {
            Await::f2c(function () use ($member, $role): Generator {
                MySQLCredentials::executeInsert("guild.add_member", [
                    'guild_id' => $this->getGuildId(),
                    'role' => $role,
                    'player_name' => $member,
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_ROLES, [$member, $role]);
            }, catches: function (Throwable $error) {
                $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);
            });
        }
    }

    /**
     * @param Player|string $member
     * @param bool $update
     */
    public function removeMember(Player|string $member, bool $update = false): void
    {
        if ($member instanceof Player) {
            $target = $member;
            $member = $member->getName();
        } else {
            $target = Server::getInstance()->getPlayerExact($member);
        }

        if (($key = array_search($member, $this->members, true)) === false) {
            return;
        }
        unset($this->members[$key]);

        if (($key = array_search($member, $this->officers, true)) !== false) {
            unset($this->officers[$key]);
        }

        if ($target !== null) {
            $playerData = $this->getManager()->getSocialManager()->getPlugin()->getPlayerData();
            $playerData->setValue($target, PlayerData::GUILD, 0);
        }

        if ($update) {
            Await::f2c(function () use ($member): Generator {
                MySQLCredentials::executeInsert("guild.remove_member", [
                    'guild_id' => $this->getGuildId(),
                    'player_name' => $member,
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_ROLES, [$member, self::RANK_REMOVED]);
            }, catches: function (Throwable $error) {
                $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);
            });
        }
    }

    /**
     * @return Player[]
     */
    public function getLocalOnlinePlayers(): array
    {
        $plugin = $this->getManager()->getSocialManager()->getPlugin();

        $onlinePlayers = [];
        foreach ($this->getMembers() as $member) {
            $target = $plugin->getServer()->getPlayerExact($member);
            if ($target instanceof Player) {
                $onlinePlayers[] = $target;
            }
        }

        return $onlinePlayers;
    }

    /**
     * @param Player|string $player
     * @param int $role
     * @param bool $update
     * @param callable|null $onSuccess
     */
    public function addMember(Player|string $player, int $role = self::RANK_MEMBER, bool $update = false, ?callable $onSuccess = null): void
    {
        Await::f2c(function () use ($player, $role, $update): Generator {
            if ($player instanceof Player) {
                $target = $player;
                $player = $player->getName();
            } else {
                $target = Server::getInstance()->getPlayerExact($player);
            }

            if ($update) {
                if ($this->isOperationLocked) {
                    return self::ADD_MEMBER_LOCKED;
                }

                $this->isOperationLocked = true;

                MySQLCredentials::executeSelect("guild.get_guild_count", [
                    'guild_id' => $this->getGuildId(),
                ], yield);

                $result = yield Await::ONCE;

                if (isset($result[0]) && $result[0]['members'] >= $this->maxGuildSize) {
                    return self::ADD_MEMBER_FULL;
                }
            }

            // The player is already a member, disregard anyway.
            if (in_array($player, $this->members, true)) {
                return self::ADD_MEMBER_EXISTS;
            }

            $this->members[] = $player;
            if ($role === self::RANK_OFFICER) {
                $this->officers[] = $player;
            } else if ($role === self::RANK_LEADER) {
                $this->leader = $player;
            }

            if ($target !== null) {
                $playerData = $this->getManager()->getSocialManager()->getPlugin()->getPlayerData();
                $playerData->setValue($target, PlayerData::GUILD, $this->getGuildId());
            }

            if ($update) {
                MySQLCredentials::executeInsert("guild.add_member", [
                    'guild_id' => $this->getGuildId(),
                    'role' => $role,
                    'player_name' => $player,
                ], yield, yield Await::REJECT);

                yield Await::ONCE;

                MySQLCredentials::executeSelect("guild.get_guild_count", [
                    'guild_id' => $this->getGuildId(),
                ], yield);

                $result = yield Await::ONCE;

                // Verify if the members does not reach the maximum members, (They can actually invite more than 1 players
                // if the database was lagging at condition "add_member", whereas the first check will be useless).
                if (isset($result[0]) && $result[0]['members'] > $this->maxGuildSize) {
                    MySQLCredentials::executeInsert("guild.remove_member", [
                        'guild_id' => $this->getGuildId(),
                        'player_name' => $player,
                    ], yield, yield Await::REJECT);

                    yield Await::ONCE;

                    $this->removeMember($player);

                    return self::ADD_MEMBER_FULL;
                }

                $this->getManager()->sendGuildMessage($this, TextFormat::AQUA . $player . ' §6joined the guild.');
                $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_ROLES, [$player, $role]);
            }

            return self::ADD_MEMBER_OK;
        }, function (int $status) use ($update, $onSuccess): void {
            if ($update && $status !== self::ADD_MEMBER_LOCKED) {
                $this->isOperationLocked = false;
            }

            if ($onSuccess !== null) {
                $onSuccess($status);
            }
        }, function (Throwable $error) use ($update, $onSuccess): void {
            if ($onSuccess !== null) {
                $onSuccess(self::ADD_MEMBER_ERROR);
            }

            if ($update) {
                $this->isOperationLocked = false;
            }

            $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);
        });
    }

    public function setDisabled(bool $disabled, bool $update = false): void
    {
        $this->disabled = $disabled;

        if ($update) {
            MySQLCredentials::executeChange('guild.set_disabled', ['guild_id' => $this->getGuildId(), 'disabled' => $disabled ? 1 : 0]);

            $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_DISABLE, [$disabled]);
        }
    }

    public function setMotd(string $motd, bool $update = false): void
    {
        $this->motd = TextFormat::clean($motd);

        if ($update) {
            MySQLCredentials::executeChange('guild.set_motd', ['guild_id' => $this->getGuildId(), 'motd' => TextFormat::clean($motd)]);

            $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_MOTD, [$motd]);
        }
    }

    public function setTag(string $tag, bool $update = false): void
    {
        if ($this->getXpLevel() < 25) {
            return;
        }

        $this->tag = $tag;

        $plugin = $this->getManager()->getSocialManager()->getPlugin();
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        foreach ($this->getMembers() as $member) {
            $target = $plugin->getServer()->getPlayerExact($member);
            if ($target instanceof Player && $target->getWorld() === $defaultWorld) {
                $target->setNameTag($plugin->getPlayerManager()->getNameTag($target));
            }
        }

        if ($update) {
            MySQLCredentials::executeChange('guild.set_tag', ['guild_id' => $this->getGuildId(), 'tag' => $tag]);

            $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_CHANGE_TAG, [$tag]);
        }
    }

    // ------------------------------------------------ GETTER OBJECTS -------------------------------------------------

    public function getXpLevel(): int
    {
        return (int)ExperienceUtils::getLevelFromXp($this->getXp());
    }

    /**
     * @return int The experience point for this guild.
     */
    public function getXp(): int
    {
        $this->updateXpData();

        return $this->xp;
    }

    private function updateXpData(): void
    {
        if ($this->xpOperationLocked) {
            return;
        }

        $this->xpOperationLocked = true;

        MySQLCredentials::executeSelect("guild.get_xp", [
            'guild_id' => $this->getGuildId(),
        ], function (array $rows) {
            $this->xpOperationLocked = false;

            if (!isset($rows[0]['xp'])) {
                return;
            }

            $this->xp = $rows[0]['xp'];
        }, function (Throwable $error): void {
            $this->xpOperationLocked = false;

            $this->getManager()->getSocialManager()->getPlugin()->getLogger()->logException($error);
        });
    }

    public function addXp(int $amount, bool $update = true): void
    {
        $this->xp += $amount;

        if ($update) {
            MySQLCredentials::executeChange('guild.add_xp', ['guild_id' => $this->getGuildId(), 'xp' => $amount]);

            $this->getManager()->broadcastEvent($this, GuildChannel::EVENT_ADD_XP, [$amount]);
        }
    }

    /**
     * @return string The name of the object guild
     */
    public function getGuildName(): string
    {
        return $this->guildName;
    }

    /**
     * @return int The maximum members can be added into this guild.
     */
    public function getMaxGuildSize(): int
    {
        return $this->maxGuildSize;
    }

    /**
     * @return string[]
     */
    public function getStats(): array
    {
        $data = [
            'leader' => $this->getLeader(),

            'tag' => $this->getTag(),
            'motd' => $this->getMotd(),
            'level' => $this->getXpLevel(),

            'officers' => $this->getOfficers(),
            'members' => $this->getMembers(),
        ];

        $content = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $content[] = TextFormat::GREEN . ucfirst($key) . ": " . TextFormat::WHITE . $value;
        }

        return $content;
    }

    public function getTag(): string
    {
        if ($this->isDisabled()) {
            return '';
        }

        return $this->tag;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @return string The message of the day for the guild
     */
    public function getMotd(): string
    {
        return $this->motd;
    }
}
