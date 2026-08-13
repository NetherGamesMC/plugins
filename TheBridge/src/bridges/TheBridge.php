<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use bridges\utils\BridgeArenaConfig;
use libminigames\Arena;
use libminigames\Minigame;
use libminigames\TeamArena;
use libminigames\utils\Autoloader;
use libminigames\utils\LeaderboardData;
use muqsit\invmenu\InvMenuHandler;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\utils\Config;
use function dirname;

Autoloader::initAutoloader(dirname(__FILE__, 3) . '/vendor/autoload.php');

class TheBridge extends Minigame
{
    /** @var BridgeArenaConfig */
    private BridgeArenaConfig $arenaConfig;
    /** @var LeaderboardData */
    private LeaderboardData $leaderboards;

    public function registerClasses(): void
    {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new BridgeArenaConfig($arenaConfig);

        $this->getServer()->getCommandMap()->register(BridgeCommand::class, new BridgeCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new BridgeListener($this), $this);

        $this->leaderboards = new LeaderboardData($this->getModes());
        foreach ($this->getModes() as $i => $mode) {
            $this->getLeaderboards()->load('tb_*mode*_wins', $i, -1, '§l§a*MODE* WINS LEADERBOARD', '§7Most The Bridge *mode* wins');
            $this->getLeaderboards()->load('tb_*mode*_kills', $i, -1, '§l§a*MODE* KILLS LEADERBOARD', '§7Most The Bridge *mode* kills');
            $this->getLeaderboards()->load('tb_*mode*_goals', $i, -1, '§l§a*MODE* GOALS LEADERBOARD', '§7Most The Bridge *mode* goals');
        }
    }

    public function getLeaderboards(): LeaderboardData
    {
        return $this->leaderboards;
    }

    public function getModes(): array
    {
        return [
            TeamArena::MODE_SOLO => 'Solo',
            TeamArena::MODE_DOUBLES => 'Doubles',
            //TeamArena::MODE_SQUADS => 'Squads'
        ];
    }

    public function getMaps(bool $onlyEnabled): array
    {
        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: "/^([a-zA-Z]-)?TB-([a-zA-Z0-9]+)/",
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }

    public function getMinigameTag(): string
    {
        return ServerManager::TB;
    }

    public function getArenaConfig(): BridgeArenaConfig
    {
        return $this->arenaConfig;
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new BridgeArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }
}
