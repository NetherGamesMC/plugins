<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
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

namespace murdermystery\gamemodes;

use libminigames\Arena;
use libminigames\ArenaListener;
use libminigames\Minigame;
use libminigames\settings\GameSettings;
use murdermystery\gamemodes\maps\MMMap;
use murdermystery\MMSettings;
use murdermystery\MurderMystery;
use murdermystery\tasks\CountDownTask;
use murdermystery\utils\StatsData;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\Sound;
use function array_keys;
use function array_rand;
use function count;
use function min;

abstract class MMArena extends Arena
{
    public const MODE_CLASSIC = 1;
    public const MODE_INFECTION = 2;

    /** @var MMMap */
    protected MMMap $map;

    public function __construct(MurderMystery $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame, new MMSettings());

        $this->statsData = new StatsData($plugin->getModeName($modeId));

        $arenas = $this->getPlugin()->getMaps(!$privateGame);
        $maps = $privateGame ? array_keys($arenas) : array_rand($arenas, min(5, count($arenas)));

        if (is_array($maps)) {
            foreach ($maps as $map) {
                $this->maps[] = $arenas[$map];
            }
        } else {
            $this->maps[] = $arenas[$maps];
        }
    }

    /**
     * @return MurderMystery
     */
    public function getPlugin(): Minigame
    {
        /** @var MurderMystery $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }

    public function startGame(): void
    {
        $this->map = MMMap::newInstance($this);

        foreach ($this->getPlayers() as $p) {
            $this->getWorld()->addSound($p->getLocation(), new BlazeShootSound(), [$p]);

            if ($p instanceof NGPlayer) {
                $p->setEnergized();
            }

            if ($this->getGameSettings()->hasNoProtection()) {
                $p->setGamemode(GameMode::SURVIVAL);
            }

            if ($this->getGameSettings()->hasRevealIdentities()) {
                $p->setNameTag($this->getPlugin()->getEssentials()->getPlayerManager()->getNameTag($p, TextFormat::GREEN, true, true));
            }
        }
    }

    /**
     * @param array<Player> $winners
     * @return void
     */
    public function setWinners(array $winners): void
    {
        $statsData = $this->getStatsData();

        foreach ($winners as $player) {
            $statsData->addValue($player, StatsData::WINS);
            $statsData->addValue($player, StatsData::MM_WINS);
            $statsData->addValue($player, StatsData::MM_MODE_WINS);
        }
    }

    final public function getMapFeatures(): MMMap
    {
        return $this->map;
    }

    /**
     * @return MMArenaListener
     */
    public function getListener(): ArenaListener
    {
        /** @var MMArenaListener $listener */
        $listener = parent::getListener();

        return $listener;
    }

    public function bootMinigame(): void
    {
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    /**
     * @param Sound|string $sound
     * @param float $volume
     * @param float $pitch
     */
    final public function broadcastSound($sound, float $volume = 1.0, float $pitch = 1.0): void
    {
        $players = $this->getPlayers();

        if ($sound instanceof Sound) {
            foreach ($players as $player) {
                $this->getWorld()->addSound($player->getLocation(), $sound, [$player]);
            }
        } else {
            foreach ($players as $player) {
                /** @var NGPlayer $player */
                $player->playSound($sound, $volume, $pitch);
            }
        }
    }

    public function queuePlayer(Player $player): void
    {
        $this->getPlugin()->getChanceHandler()->addArenaPlayer($this, $player);

        parent::queuePlayer($player);
    }

    public function getMaxSize(): int
    {
        return 16;
    }

    /**
     * This is the popup showed during the countdown task
     * @param Player $player
     * @return string
     */
    abstract public function getWaitingPopup(Player $player): string;

    /**
     * @return MMSettings
     */
    public function getGameSettings(): GameSettings
    {
        /** @var MMSettings $gameSettings */
        $gameSettings = parent::getGameSettings();

        return $gameSettings;
    }
}