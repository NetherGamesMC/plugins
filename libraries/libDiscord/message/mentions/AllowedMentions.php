<?php
/**
 *  _ _ _     _____  _                       _
 * | (_) |   |  __ \(_)                     | |
 * | |_| |__ | |  | |_ ___  ___ ___  _ __ __| |
 * | | | '_ \| |  | | / __|/ __/ _ \| '__/ _` |
 * | | | |_) | |__| | \__ \ (_| (_) | | | (_| |
 * |_|_|_.__/|_____/|_|___/\___\___/|_|  \__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libDiscord\message\mentions;

use InvalidArgumentException;
use JsonSerializable;
use libDiscord\message\MessageUtils;

class AllowedMentions implements JsonSerializable
{
    /**
     * A list of allowed MentionTypes.
     * @var string[] $types
     */
    public array $types = [];
    /**
     * A list of user IDs that are allowed to be mentioned
     * @var string[] $users
     */
    public array $users = [];
    /**
     * A list of role IDs that are allowed to be mentioned
     * @var string[] $roles
     */
    public array $roles = [];

    /**
     * @param string[] $types
     * @param string[] $users
     * @param string[] $roles
     */
    public function __construct(array $types = [], array $users = [], array $roles = [])
    {
        foreach ($types as $type) $this->addType($type);
        foreach ($users as $user) $this->addUser($user);
        foreach ($roles as $role) $this->addRole($role);
    }

    public static function none(): AllowedMentions
    {
        return new AllowedMentions();
    }

    public function addType(string $type): self
    {
        if (!MentionType::isValid($type)) {
            throw new InvalidArgumentException("Invalid mention type: $type");
        }
        $this->types[] = $type;
        return $this;
    }

    public function addUser(string $userId): self
    {
        if (!MessageUtils::isValidUserMention($userId)) {
            throw new InvalidArgumentException("Invalid user ID");
        }
        $this->users[] = $userId;
        return $this;
    }

    public function addRole(string $roleId): self
    {
        if (!MessageUtils::isValidRoleMention($roleId)) {
            throw new InvalidArgumentException("Invalid role ID");
        }
        $this->roles[] = $roleId;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "parse" => $this->types,
            "users" => $this->users,
            "roles" => $this->roles,
        ];
    }
}