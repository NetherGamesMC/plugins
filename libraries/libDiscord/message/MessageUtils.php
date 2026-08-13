<?php

namespace libDiscord\message;

class MessageUtils
{

    public static function createUserMention(string $id): string
    {
        return "<@$id>";
    }

    public static function createRoleMention(string $id): string
    {
        return "<@&$id>";
    }

    public static function isValidUserMention(string $mention): bool
    {
        return preg_match("/<@!?[0-9]+>/", $mention) === 1;
    }

    public static function isValidRoleMention(string $mention): bool
    {
        return preg_match("/<@&[0-9]+>/", $mention) === 1;
    }

}