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

class MentionType
{
    /** This is used for both @everyone / @here */
    public const EVERYONE = "everyone";
    /** If used, this will overwrite the allowed users */
    public const USERS = "users";
    /** If used, this will overwrite the allowed roles */
    public const ROLES = "roles";

    public static function isValid(string $type): bool
    {
        return in_array($type, [self::EVERYONE, self::USERS, self::ROLES]);
    }
}