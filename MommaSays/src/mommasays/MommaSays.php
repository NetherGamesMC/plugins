<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\utils\ArenaConfig;
use mommasays\utils\data\BlockSets;
use mommasays\utils\MSArenaConfig;
use mommasays\utils\StatsData;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class MommaSays extends Minigame
{
    /** @var MSArenaConfig */
    private MSArenaConfig $arenaConfig;

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new MSArenaConfig($arenaConfig);
        $this->getServer()->getCommandMap()->register(MSCommand::class, new MSCommand($this));
        BlockSets::init();

        $this->getServer()->getPluginManager()->registerEvents(new MSListener($this), $this);
    }

    public function getModes(): array
    {
        return [
            MSArena::MODE_SIMON_SAYS => 'Momma Says'
        ];
    }

    public function isStandAloneGame(): bool
    {
        return true;
    }

    /**
     * @param Player $player
     * @return MSArena|null
     */
    public function getArena(Player $player): ?Arena
    {
        /** @var MSArena $arena */
        $arena = parent::getArena($player);

        return $arena;
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new MSArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function getArenaConfig(): ArenaConfig
    {
        return $this->arenaConfig;
    }
}