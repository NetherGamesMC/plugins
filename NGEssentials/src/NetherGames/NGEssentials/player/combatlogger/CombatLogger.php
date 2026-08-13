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

namespace NetherGames\NGEssentials\player\combatlogger;

use NetherGames\NGEssentials\NGEssentials;
use pocketmine\player\Player;

class CombatLogger
{
    /** @var array<int, CombatLog> */
    private array $log = [];

    public function __construct(NGEssentials $plugin)
    {
        $plugin->getServer()->getPluginManager()->registerEvents(new CombatListener($this), $plugin);
    }

    public function getLatestHit(Player $player, bool $void = true): ?Player
    {
        $log = $this->log[$player->getId()] ?? null;
        if ($log === null) {
            return null;
        }

        $hit = $log->getLatestHit();
        if ($hit === null) {
            return null;
        }

        $this->wipeLog($player, true);

        return $player->getServer()->getPlayerExact($hit->getDamagerName());
    }

    public function wipeLog(Player $player, bool $lastLog = false): void
    {
        if ($lastLog) {
            unset($this->log[$player->getId()]);
        } elseif (isset($this->log[$player->getId()])) {
            $this->log[$player->getId()]->wipeLog();
        }
    }

    public function getLog(Player $player): CombatLog
    {
        $playerId = $player->getId();

        if (!isset($this->log[$playerId])) {
            $this->log[$playerId] = new CombatLog();
        }

        return $this->log[$playerId];
    }
}