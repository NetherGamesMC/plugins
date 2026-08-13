<?php

namespace NetherGames\NGEssentials\player\social\friends;

use NetherGames\NGEssentials\servers\Server;

final class PlayerFriendsInfo
{
    /** @required */
    public string $playerName;

    /** @required */
    public bool $online = false;

    /** @required */
    public ?Server $server = null;
}