<?php

declare(strict_types=1);

namespace uhc;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\TeamArena;
use libminigames\utils\ArenaConfig;
use libVanilla\VanillaPlugin;
use pocketmine\utils\Config;
use uhc\command\UHCCommand;
use uhc\game\UHCArena;
use uhc\game\UHCTeam;
use uhc\utils\StatsData;
use uhc\utils\UHCArenaConfig;

class UHC extends Minigame
{
    /** @var UHCArenaConfig */
    private UHCArenaConfig $arenaConfig;

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new UHCArenaConfig($arenaConfig);

        VanillaPlugin::ENCHANTS()->register($this);

        $this->replaySystemStatus = self::REPLAY_SYSTEM_STATUS_OFF;

        $this->getServer()->getCommandMap()->register("uhc", new UHCCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new UHCListener($this), $this);
    }

    /**
     * @return string[]
     */
    public function getModes(): array
    {
        return [
            TeamArena::MODE_SOLO => "Solo",
            TeamArena::MODE_DOUBLES => "Doubles",
            TeamArena::MODE_TRIOS => "Trios",
            TeamArena::MODE_SQUADS => "Squads"
        ];
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new UHCArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function getTeamByXuid(string $xuid): ?UHCTeam
    {
        /** @var UHCArena $arena */
        foreach ($this->getArenas() as $arena) {
            /** @var UHCTeam $team */
            foreach ($arena->getTeams() as $team) {
                if (in_array($xuid, $team->getXuidCache(), true)) {
                    return $team;
                }
            }
        }

        return null;
    }

    public function getArenaConfig(): ArenaConfig
    {
        return $this->arenaConfig;
    }
}
