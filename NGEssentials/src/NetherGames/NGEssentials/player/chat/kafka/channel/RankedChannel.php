<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;

class RankedChannel extends RankChannel
{
    /** @var PlayerData */
    private PlayerData $playerData;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct(ChatChannel::CHANNEL_RANKED, $plugin->getServer(), [Permissions::RANK_ULTRA, ...Permissions::STAFF_RANKS]);

        $this->playerData = $plugin->getPlayerData();
    }

    public function canBeUsed(Player $player): bool
    {
        return parent::canBeUsed($player) && $this->playerData->getBool($player, PlayerData::RANKED_CHAT);
    }
}