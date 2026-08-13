<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\utils\ArenaConfig;
use pocketmine\utils\Config;
use soccer\utils\SCArenaConfig;
use soccer\utils\StatsData;

class Soccer extends Minigame
{
    /** @var SCArenaConfig */
    private SCArenaConfig $arenaConfig;

    public function getModes(): array
    {
        return [
            SCArena::MODE_SOCCER => 'Soccer'
        ];
    }

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new SCArenaConfig($arenaConfig);

        SCBall::setup();
        $this->replaySystemStatus = self::REPLAY_SYSTEM_STATUS_OFF;

        $this->getServer()->getCommandMap()->register(SCCommand::class, new SCCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new SCListener($this), $this);
    }

    public function balanceQueuing(): bool
    {
        return true;
    }

    public function isStandAloneGame(): bool
    {
        return true;
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new SCArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function getArenaConfig(): ArenaConfig
    {
        return $this->arenaConfig;
    }
}