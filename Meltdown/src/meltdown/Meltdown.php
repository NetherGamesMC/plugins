<?php

namespace meltdown;

use libminigames\Minigame;
use NetherGames\NGEssentials\entity\custom\CustomActorList;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\utils\Config;
use meltdown\arena\MDArena;
use meltdown\arena\MDArenaConfig;

class Meltdown extends Minigame
{

    /** @var int Required minimum player count for start the game */
    public static int $MINIMUM_PLAYERS = 4;

    /** @var int Max slot size for an arena */
    public static int $MAX_SIZE = 12;

    /** @var int Game playing time (in seconds) */
    public static int $PLAYING_TIME = 400;

    /** @var MDArenaConfig */
    private MDArenaConfig $arenaConfig;

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new MDArenaConfig($arenaConfig);

        $snowBlastResistance = VanillaBlocks::SNOW()->getBreakInfo()->getBlastResistance();
        foreach([VanillaBlocks::ICE(), VanillaBlocks::PACKED_ICE(), VanillaBlocks::BLUE_ICE()] as $block){ // break all blocks equally
            RuntimeBlockStateRegistry::getInstance()->blastResistance[$block->getStateId()] = $snowBlastResistance;
        }

        $this->getServer()->getCommandMap()->register(MDCommand::class, new MDCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new MDEventListener($this), $this);
    }

    /**
     * @return string[]
     */
    public function getModes(): array
    {
        return [
            MDArena::MODE_NORMAL => "Normal",
        ];
    }

    /**
     * @param int $modeId
     * @param bool $privateGame
     * @return MDArena
     */
    public function generateNewArena(int $modeId, bool $privateGame = false): MDArena
    {
        return new MDArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function isStandAloneGame(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getMaps(bool $onlyEnabled): array
    {
        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: "/^([a-zA-Z0-9]+)*MD-([a-zA-Z0-9]+)/",
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }

    /**
     * @return MDArenaConfig
     */
    public function getArenaConfig(): MDArenaConfig
    {
        return $this->arenaConfig;
    }
}