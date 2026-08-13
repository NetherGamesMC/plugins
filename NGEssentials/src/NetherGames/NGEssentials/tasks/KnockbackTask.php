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
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\tasks;

use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\World;
use function array_filter;

class KnockbackTask extends BaseTask
{
    public function onRun(): void
    {
        $plugin = $this->getPlugin();
        $server = $plugin->getServer();
        /** @var World $defaultWorld */
        $defaultWorld = $server->getWorldManager()->getDefaultWorld();
        $playerData = $plugin->getPlayerData();

        $players = array_filter($defaultWorld->getPlayers(), function (Player $player) use ($playerData): bool {
            return !$player->isSpectator() && $playerData->getBool($player, PlayerData::KNOCKBACK) && $playerData->getString($player, PlayerData::SELECTED_RANK) === '' && $player->hasPermission(Permissions::RANK_ADVISOR) && !$this->getPlugin()->getPlayerData()->getBool($player, PlayerData::NICK);
        });

        foreach ($players as $player) {
            $playerLocation = $player->getLocation();

            foreach ($defaultWorld->getNearbyEntities(new AxisAlignedBB($playerLocation->getX() - 2, $playerLocation->getY() - 2, $playerLocation->getZ() - 2, $playerLocation->getX() + 2, $playerLocation->getY() + 2, $playerLocation->getZ() + 2)) as $entity) {
                if (($entity instanceof Player) && $entity->getId() !== $player->getId()) {
                    $entityLocation = $entity->getLocation();

                    $entity->knockBack($entityLocation->getX() - ($playerLocation->getX() + 2), $entityLocation->getZ() - ($playerLocation->getZ() + 2), 2 / 0xa);
                }
            }
        }
    }
}