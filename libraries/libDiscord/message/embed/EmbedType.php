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

namespace libDiscord\message\embed;

/**
 * As noted by Discord, embed types are loosely defined and not often used.
 * Though they have not been removed from the API yet, they should be considered deprecated.
 * For more info, see https://discord.com/developers/docs/resources/channel#embed-object-embed-types.
 */
class EmbedType
{
    public const RICH = "rich";
    public const IMAGE = "image";
    public const VIDEO = "video";
    public const GIFV = "gifv";
    public const ARTICLE = "article";
    public const LINK = "link";
}