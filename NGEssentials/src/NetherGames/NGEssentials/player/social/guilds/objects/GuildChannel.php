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
 * @author k3ithos, matcracker, driesboy, larryTheCoder
 *
 */

namespace NetherGames\NGEssentials\player\social\guilds\objects;

interface GuildChannel
{
    public const EVENT_GUILD_DISBAND = 0;       // OK
    public const EVENT_CHANGE_LEADER = 1;       // OK
    public const EVENT_UPDATE_PLAYER_NAME = 2;  // OK
    public const EVENT_CHANGE_GUILD_NAME = 3;   // OK
    public const EVENT_CHANGE_ROLES = 4;        // OK
    public const EVENT_CHANGE_TAG = 5;          // OK
    public const EVENT_ADD_XP = 6;              // OK
    public const EVENT_CHANGE_DISABLE = 7;      // OK
    public const EVENT_CHANGE_MOTD = 8;         // OK

}