<?php

namespace NetherGames\NGEssentials\player\social;

final class PlayerSocialInfo
{
    /** @required */
    public string $sourceUid;

    /** @required */
    public string $proxyId;

    /** @required */
    public string $playerName;

    /** @required */
    public string $playerIdentifier;

    /** @required */
    public string $location;

    /** @required */
    public string $address;

    /** @required */
    public string $connectionId;
}