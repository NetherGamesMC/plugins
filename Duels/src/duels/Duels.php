<?php
/**
 *        _____             _
 *       |  __ \           | |
 *  __  _| |  | |_   _  ___| |___
 *  \ \/ / |  | | | | |/ _ \ / __|
 *   >  <| |__| | |_| |  __/ \__ \
 *  /_/\_\_____/ \__,_|\___|_|___/
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

namespace duels;

use duels\utils\DuelsArenaConfig;
use duels\utils\StatsData;
use libminigames\Arena;
use libminigames\Minigame;
use libVanilla\VanillaPlugin;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\utils\Config;
use function array_filter;
use function is_dir;

class Duels extends Minigame
{
    /** @var DuelsArenaConfig */
    private DuelsArenaConfig $arenaConfig;

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new DuelsArenaConfig($arenaConfig);

        VanillaPlugin::FISHING_ROD()->register($this);

        $this->getServer()->getCommandMap()->register(DuelsCommand::class, new DuelsCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new DuelsListener($this), $this);
    }

    public function getModes(): array
    {
        return [
            DuelsArena::MODE_SOLO => 'Solo',
            DuelsArena::MODE_DOUBLES => 'Doubles',
        ];
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new DuelsArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function getMinigameTag(): string
    {
        return ServerManager::DUELS;
    }

    public function getMaps(bool $isSumo, bool $onlyEnabled): array
    {
        $pattern = $isSumo ? "/^([a-zA-Z]-)?DLS-Sumo-([a-zA-Z0-9_]+)$/" : "/^([a-zA-Z]-)?DLS-([a-zA-Z0-9]+)$/";

        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: $pattern,
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }

    public function getArenaConfig(): DuelsArenaConfig
    {
        return $this->arenaConfig;
    }
}