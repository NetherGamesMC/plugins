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

namespace murdermystery\gamemodes\classic;

use libminigames\ArenaListener;
use murdermystery\gamemodes\classic\tasks\MatchTimeClassicTask;
use murdermystery\gamemodes\MMArena;
use murdermystery\MurderMystery;
use murdermystery\utils\Items;
use murdermystery\utils\MMChance;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

class MMArenaClassic extends MMArena
{
    /** @var Player|null $detective */
    private ?Player $detective = null;
    /** @var Player|null $murderer */
    private ?Player $murderer = null;
    /** @var Player[] $innocents */
    private array $innocents = [];
    /** @var Player|null */
    private ?Player $murdererKilled = null;

    public function __construct(MurderMystery $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);

        $this->listener = new MMArenaListenerClassic($this);
    }

    /**
     * @return MMArenaListenerClassic
     */
    public function getListener(): ArenaListener
    {
        /** @var MMArenaListenerClassic $listener */
        $listener = parent::getListener();

        return $listener;
    }

    public function getDetective(): ?Player
    {
        return $this->detective;
    }

    public function setDetective(?Player $detective = null): void
    {
        $this->detective = $detective;
    }

    public function setMurdererKilled(Player $player): void
    {
        $this->murdererKilled = $player;
    }

    public function removeInnocent(Player $innocent): void
    {
        $this->innocents = array_diff($this->innocents, [$innocent]);

        $this->getScoreboard()->setLine($this->getPlayers(), 8, CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($this->getInnocents()));
    }

    /**
     * @return Player[]
     */
    public function getInnocents(): array
    {
        return $this->innocents;
    }

    /**
     * @param Player[] $innocents
     */
    public function setInnocents(array $innocents): void
    {
        $this->innocents = $innocents;
    }

    public function isInnocent(Player $player): bool
    {
        return in_array($player, $this->innocents, true);
    }

    public function startGame(): void
    {
        /** @var Player[] $innocents */
        $innocents = [];

        [$murderer, $detective] = $this->getPlugin()->getChanceHandler()->getHighestChances($this);
        $this->murderer ??= $murderer;
        $this->detective ??= $detective;

        $array = [];

        $array[11] = '';
        $array[10] = CustomIcon::GAMEMODE;
        $array[9] = '';
        $array[8] = CustomIcon::STEVE_HEAD . TextFormat::GREEN . (count($this->getAlivePlayers()) - 1);
        $array[7] = CustomIcon::HOURGLASS . TextFormat::GREEN . '5:00';
        $array[6] = '';
        $array[5] = CustomIcon::SEARCH . TextFormat::GREEN . 'Alive';
        $array[4] = '';
        $array[3] = CustomIcon::MAP . TextFormat::GREEN . $this->getMapDisplayName();
        $array[2] = '';
        $array[1] = CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co';


        foreach ($this->getPlayers() as $player) {
            if ($this->isSpectator($player)) {
                $array[10] = CustomIcon::GAMEMODE . TextFormat::GREEN . 'Spectator';
            } else {
                $player->setNameTag(''); //TODO: Remove when MCPE fix that
                if ($this->isMurderer($player)) {
                    $player->sendTitle('§cYOU ARE THE MURDERER', '§6GOAL: §eKill all players!', 0, 60, 20);
                    $array[10] = CustomIcon::GAMEMODE . TextFormat::GREEN . 'Murderer';

                    if ($this->getGameSettings()->hasAlwaysCompass()) {
                        $player->getInventory()->setItem(Items::COMPASS, Items::getInnocentLocator());
                    }
                } else {
                    $innocents[] = $player;
                    if ($this->isDetective($player)) {
                        $player->sendTitle('§bYOU ARE THE DETECTIVE', '§6GOAL: §eFind and kill the Murderer!', 0, 100, 20);
                        $array[10] = CustomIcon::GAMEMODE . TextFormat::GREEN . 'Detective';
                    } else {
                        $player->sendTitle('§aYOU ARE INNOCENT', '§6GOAL: §eStay alive as long as possible!', 0, 60, 20);
                        $array[10] = CustomIcon::GAMEMODE . TextFormat::GREEN . 'Innocent';
                    }
                }
            }
            $player->teleport($this->getPlugin()->getArenaConfig()->getRandomSpawn($this));

            $this->getScoreboard()->setLines([$player], $array);
            $this->assignChance($player);
        }

        $this->setInnocents($innocents);

        parent::startGame();

        $this->getPlugin()->getChanceHandler()->removeArenaChance($this);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeClassicTask($this), 20);
    }

    public function isMurderer(Player $player): bool
    {
        return $this->murderer === $player;
    }

    public function isDetective(Player $player): bool
    {
        return $this->detective === $player;
    }

    public function assignChance(Player $player): void
    {
        $chanceHandler = $this->getPlugin()->getChanceHandler();

        if ($this->isMurderer($player)) {
            $chanceHandler->setPlayerChance($player, MMChance::MURDERER, 1);
        } elseif ($this->isDetective($player)) {
            $chanceHandler->setPlayerChance($player, MMChance::DETECTIVE, 1);
        } else {
            $chanceHandler->addPlayerChange($player, MMChance::MURDERER, mt_rand(1, 2));
            $chanceHandler->addPlayerChange($player, MMChance::DETECTIVE, mt_rand(1, 2));
        }
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        $murderer = $this->getMurderer();

        if ($murderer === null) {
            if ($player === $this->getMurderKiller()) {
                $data[self::DATA_XP][] = [
                    'Killed The Murderer',
                    9,
                ];

                $data[self::DATA_CREDITS][] = [
                    'Killed The Murderer',
                    $this->isDetective($player) ? 12 : 15
                ];
            }
        }

        if ($this->isWinner($player)) {
            if ($player === $murderer) {
                $data[self::DATA_CREDITS][] = [
                    'Win As Murderer',
                    10,
                ];
            } elseif ($player !== $this->getMurderKiller()) {
                $data[self::DATA_CREDITS][] = [
                    'Win As ' . ($this->isDetective($player) ? "Detective" : "Innocent"),
                    8,
                ];
            }
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function getMurderer(): ?Player
    {
        return $this->murderer;
    }

    public function setMurderer(?Player $murderer = null): void
    {
        $this->murderer = $murderer;
    }

    public function getMurderKiller(): ?Player
    {
        return $this->murdererKilled;
    }

    public function getStreaksKey(): ?string
    {
        if ($this->isPrivateGame()) return null;
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    final public function getWaitingPopup(Player $player): string
    {
        return '§cMurderer chance:§6 ' . $this->getPlugin()->getChanceHandler()->getChancePercentage($this, $player, MMChance::MURDERER) . '%% §7- §bDetective chance:§6 ' . $this->getPlugin()->getChanceHandler()->getChancePercentage($this, $player, MMChance::DETECTIVE) . '%%';
    }
}