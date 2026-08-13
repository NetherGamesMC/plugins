<?php

namespace NetherGames\NGEssentials\player\chat\kafka\channel;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;

class StaffChannel extends RankChannel
{
    /** @var PlayerData */
    private readonly PlayerData $playerData;

    /**
     * @param string[] $permissions
     */
    public function __construct(int $channelId, NGEssentials $plugin, array $permissions)
    {
        parent::__construct($channelId, $plugin->getServer(), $permissions);

        $this->playerData = $plugin->getPlayerData();
    }

    public function canBeUsed(Player $player): bool
    {
        return parent::canBeUsed($player) && $this->playerData->getBool($player, PlayerData::STAFF_NOTIFICATIONS);
    }
}