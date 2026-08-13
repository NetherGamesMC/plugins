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

namespace NetherGames\NGEssentials\player\cosmetics\tasks;

use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;
use function array_filter;
use function cos;
use function count;
use function deg2rad;
use function mt_rand;
use function sin;

class CosmeticTask extends Task
{
    /** @var int */
    private int $degrees = 0;

    public function __construct(private CosmeticHandler $handler)
    {
    }

    public function onRun(): void
    {
        $plugin = $this->handler->getPlugin();
        $server = $plugin->getServer();

        /** @var World $defaultWorld */
        $defaultWorld = $server->getWorldManager()->getDefaultWorld();
        $zoneManager = $plugin->getPlayerManager()->getWorldFeatures()->getZonesManager();

        $players = array_filter($defaultWorld->getPlayers(), function (Player $player) use ($zoneManager): bool {
            /** @var NGPlayer $player */
            $isNick = $this->handler->getPlugin()->getPlayerData()->getBool($player, PlayerData::NICK);
            return $player->isLoaded() && !$player->isSpectator() && $zoneManager?->getZone($player) === null && !$isNick;
        });

        if (count($players) < ($plugin->getServerManager()->getMaxPlayers() / 1.25)) {
            $currentTick = $server->getTick();
            $runParticles = $currentTick % 5 === 0;
            if ($runParticles) {
                $runWings = $currentTick % 20 === 0;
            } else {
                $runWings = false;
            }

            $x = cos(deg2rad($this->degrees)) * 0.6;
            $z = sin(deg2rad($this->degrees)) * 0.6;

            foreach ($players as $player) {
                $pos = $player->getPosition();

                CosmeticHandler::TRAILS()->onTick($player, Position::fromObject($pos->add($x, 0, $z), $defaultWorld));

                if ($runParticles) {
                    CosmeticHandler::PARTICLES()->onTick($player, Position::fromObject($pos->add(mt_rand(-12, 12) / 10, mt_rand(3, 15) / 10, mt_rand(-12, 12) / 10), $defaultWorld));
                }

                if ($runWings) {
                    CosmeticHandler::WINGS()->onTick($player);

                    foreach ($this->handler->getArmorCosmetics() as $cosmetic) {
                        $cosmetic->onTick($player);
                    }
                }
            }

            if ($this->degrees === 360) {
                $this->degrees = 0;
            } else {
                $this->degrees += 6;
            }
        }
    }
}