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

use JsonSerializable;

/**
 * This is the internalized IEmbed interface that is used on the inside of MessageEmbed.
 *
 * Child classes of this are attached to the encoded JSON object.
 * @see https://discord.com/developers/docs/resources/channel#embed-object
 *
 * Classes that implement this interface should have at least one static method: create.
 * This method should include all the fields that are required to be included in the JSON object.
 *
 * Optionally, if the class has a lot of fields, the class should also have another static method: simple.
 * This method should fill out some less used fields.
 *
 * Through these methods, developers should be able to easily create new instances of the class.
 */
interface IEmbed extends JsonSerializable
{
}