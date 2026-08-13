<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace skywars;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\TeamArena;
use libminigames\utils\AutoUpgrader;
use libminigames\utils\LeaderboardData;
use libVanilla\features\Feature;
use libVanilla\VanillaPlugin;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\utils\Config;
use skywars\drops\DropManager;
use skywars\kits\KitManager;
use skywars\tasks\BlockQueueTask;
use skywars\utils\SWArenaConfig;
use function array_filter;
use function str_replace;
use function str_starts_with;

class Skywars extends Minigame
{
    /** @var SWArenaConfig */
    private SWArenaConfig $arenaConfig;
    /** @var BlockQueueTask */
    private BlockQueueTask $blockQueue;
    /** @var KitManager */
    private KitManager $kitManager;
    /** @var DropManager */
    private DropManager $dropManager;
    /** @var LeaderboardData */
    private LeaderboardData $leaderboards;

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new SWArenaConfig($arenaConfig);

        foreach ($this->getRequiredFeatures() as $feature) {
            $feature->register($this);
        }

        $this->kitManager = new KitManager();
        $this->dropManager = new DropManager();

        AutoUpgrader::getInstance();

        $this->blockQueue = new BlockQueueTask($this->getServer());
        $this->getScheduler()->scheduleRepeatingTask($this->blockQueue, 2 * 20);

        $this->getServer()->getCommandMap()->register(SWCommand::class, new SWCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new SWListener($this), $this);

        $this->leaderboards = new LeaderboardData($this->getModes());
        foreach ($this->getModes() as $i => $mode) {
            $this->getLeaderboards()->load('sw_*mode*_wins', $i, -1, '§l§a*MODE* WINS LEADERBOARD', '§7Most SkyWars *mode* wins');
            $this->getLeaderboards()->load('sw_*mode*_kills', $i, -1, '§l§a*MODE* KILLS LEADERBOARD', '§7Most SkyWars *mode* kills');
        }
    }

    /**
     * @return array<Feature>
     */
    public function getRequiredFeatures(): array
    {
        return [
            VanillaPlugin::CROSSBOW(),
            VanillaPlugin::FISHING_ROD(),
            VanillaPlugin::SHIELD(),
            VanillaPlugin::TRIDENTS()
        ];
    }

    public function getModes(): array
    {
        return [
            TeamArena::MODE_SOLO => 'Solo',
            TeamArena::MODE_DOUBLES => 'Doubles',
            SWArena::MODE_DUELS_SOLO => '1v1',
            SWArena::MODE_DUELS_DOUBLES => '2v2'
        ];
    }

    public function getLeaderboards(): LeaderboardData
    {
        return $this->leaderboards;
    }

    public function getBlockQueue(): BlockQueueTask
    {
        return $this->blockQueue;
    }

    public function getKitManager(): KitManager
    {
        return $this->kitManager;
    }

    public function getDropManager(): DropManager
    {
        return $this->dropManager;
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new SWArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function getMinigameTag(): string
    {
        return ServerManager::SW;
    }

    public function getArenaConfig(): SWArenaConfig
    {
        return $this->arenaConfig;
    }

    public function getMaps(bool $isDuelsGame, bool $onlyEnabled): array
    {
        $pattern = $isDuelsGame ? "/^([a-zA-Z]-)?SWD-([a-zA-Z0-9]+)/" : "/^([a-zA-Z]-)?SW-([a-zA-Z0-9]+)/";

        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: $pattern,
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }
}