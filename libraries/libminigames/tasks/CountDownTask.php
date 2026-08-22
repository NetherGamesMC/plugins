<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\tasks;

use libminigames\Arena;
use libminigames\events\player\PlayerQuitEvent;
use libminigames\session\GameSession;
use libminigames\TeamArena;
use libminigames\utils\Icon;
use libminigames\utils\Items;
use libminigames\utils\TypeArena;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\PopSound;
use function count;

/**
 * Provides basic countdown task, this task handles queued players in a FIFO (First-In-First-Out) order.
 * You wouldn't have to extend this class as most functionalities will be provided here.
 *
 * @package libminigames\tasks
 */
class CountDownTask extends Task
{
    public const COUNTDOWN = 30;
    /** @var int */
    protected int $countdown = self::COUNTDOWN;
    /** @var string */
    protected string $color = TextFormat::YELLOW;
    /** @var bool */
    protected bool $paused = false;
    /** @var Arena */
    private Arena $arena;

    public function __construct(Arena $arena)
    {
        $this->arena = $arena;

        foreach ($arena->getPlayers() as $player) {
            $player->teleport($arena->getWaitingLobbySpawn());
        }
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $arena = $this->getArena();
        $players = $arena->getPlayers(false);

        $joinedPlayers = count($players);
        $maxPlayers = $arena->getMaxSize();

        $isPrivate = $arena->isPrivateGame();

        if ($isPrivate && $arena->shouldStartImmediately() && $this->countdown > 5) {
            $arena->getGameSettings()->setPaused(false);
            $this->setCountdown(5);
        }

        if ($this->countdown > 15 && $joinedPlayers === $maxPlayers) {
            $this->setCountdown(15);
        }

        if ($this->countdown > self::COUNTDOWN && $joinedPlayers >= $maxPlayers / 2) {
            $this->setCountdown(self::COUNTDOWN);
        }

        $arena->addQueuedPlayers();

        $isPaused = $arena->getGameSettings()->isPaused();
        $arena->refreshWaitingScoreboard($this->countdown, $isPaused);

        if ($isPaused) {
            if (!$this->paused) {
                $arena->refreshWaitingScoreboard($this->countdown, true);
            }
            $this->paused = true;
            return;
        }

        if (($joinedPlayers >= $arena->getMinimumPlayers() || $arena->shouldStartImmediately()) && (!$arena->getPlugin()->balanceQueuing() || !$arena instanceof TeamArena || $arena->areTeamsBalanced() || $isPrivate)) {
            switch ($this->countdown) {
                case 20:
                    $arena->broadcastMessage('§eThe game starts in ' . $this->color . $this->countdown . ' §eseconds!');
                    break;
                case 10:
                    $this->color = TextFormat::GOLD;

                    $arena->broadcastMessage('§eThe game starts in ' . $this->color . $this->countdown . ' §eseconds!');
                    $arena->broadcastTitle(TextFormat::GREEN . $this->countdown, ' ', 0, 20);
                    break;
                case 5:
                    $this->color = TextFormat::RED;

                    $arena->setStatus(Arena::STATUS_STARTING);

                    if ($arena instanceof TypeArena || count($arena->getMaps()) > 1) {
                        $arena->broadcastMessage(TextFormat::GOLD . 'Voting has ended!');
                        if ($arena instanceof TypeArena) {
                            $arena->checkTypeVotes();
                        }
                    }

                    foreach ($players as $player) {
                        $inventory = $player->getInventory();
                        $inventory->clear(Items::PRIVATE_GAME_SETTINGS);
                        $inventory->clear(Items::TEAM_SELECTOR);
                        $inventory->clear(Items::EXTRA_ITEM_1);
                        $inventory->clear(Items::MAP_SELECTOR);
                        $inventory->clear(Items::EXTRA_ITEM_2);
                        $inventory->clear(Items::EXTRA_ITEM_3);
                    }

                    if ($arena->getPlugin()->hasWaitingLobby()) {
                        $arena->checkMapVotes();
                        $arena->setupMap();
                    }
                    break;
                case 0:
                    $arena->start();
                    $arena->broadcastTitle(Icon::get('countdown.go', 'GO'), '', 0, 10);
                    $this->getHandler()?->cancel();
                    return;
            }

            if ($this->countdown <= 5) {
                foreach ($arena->getPlayers() as $player) {
                    if ($this->countdown <= 3) {
                        GameSession::getSession($player)->playSound('note.hat', 1, 0.943874);
                    } else {
                        $arena->getWorld()->addSound($player->getLocation(), new PopSound(), [$player]);
                    }
                }

                $arena->broadcastMessage('§eThe game starts in ' . $this->color . $this->countdown . ' §eseconds!');
                $arena->broadcastTitle(Icon::get('countdown.' . $this->countdown, (string)$this->countdown), '', 0, 20);
            }

            $arena->refreshWaitingScoreboard($this->countdown, $isPaused);
            $this->countdown--;
        } elseif ($joinedPlayers >= 1) {
            if ($this->color === TextFormat::RED) {
                $players = $arena->getPlayers();
                foreach ($players as $player) {
                    $arena->removePlayer($player, PlayerQuitEvent::FINISH);
                }
                $arena->finishGame();

                $arena->getPlugin()->removeArena($arena);

                $this->getHandler()?->cancel();

                $arena->requeuePlayers($players);
            } else {
                if ($this->countdown !== self::COUNTDOWN || $this->paused) {
                    $arena->refreshWaitingScoreboard($this->countdown, $isPaused);
                }
            }
        } else {
            $arena->getPlugin()->removeArena($arena);

            $this->getHandler()?->cancel();
        }

        if ($this->paused) {
            $this->paused = false;
        }
    }

    public function getArena(): Arena
    {
        return $this->arena;
    }

    public function setCountdown(int $countdown): void
    {
        if ($this->countdown < 5) {
            return;
        }

        if ($this->countdown < $countdown) {
            $this->getArena()->broadcastTitle(TextFormat::YELLOW . "Start postponed!", "$countdown seconds to start");
        }

        $this->countdown = $countdown;
        $this->color = match (true) {
            $countdown <= 5 => TextFormat::RED,
            $countdown <= 10 => TextFormat::GOLD,
            default => TextFormat::YELLOW,
        };
    }
}