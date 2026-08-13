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
use libminigames\TeamArena;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\tile\Chest;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use function count;

/**
 * Main matchmaking task, this is where you handle all tasks regarding your gameplay. The provided functions are given
 * to provide access to a specific moment or function. For example, {@link MatchTimeTask::gameTick()}, were used when the
 * given play-time of the arena is not more than specified {@link MatchTimeTask::getPlayingTime()}.
 *
 * In a nutshell, provided functions will be called for very own specific moment, developers will only have to
 * override them appropriately. You may read the functions documentation for more insights.
 *
 * @package libminigames\tasks
 */
abstract class MatchTimeTask extends Task
{
    /** @var int */
    protected int $time;
    /** @var int */
    protected int $timePassed = 0;
    /** @var Arena */
    private Arena $arena;

    public function __construct(Arena $arena)
    {
        $this->arena = $arena;
        $this->time = $this->getPlayingTime();
    }

    /**
     * Abstract function of the arena play-time. In unit seconds. This method is crucial when it comes to determine
     * which to execute whether {@link MatchTimeTask::gameTick()} or {@link MatchTimeTask::overTimeTick()}.
     *
     * <p>Lets assume that this function is set as variable <code>$x</code>, and total play-time is <code>$y</code>.
     * If <code>$x <= $y</code> or known as "$x less or equals than $y", {@link MatchTimeTask::gameTick()} will be called,
     * otherwise, {@link MatchTimeTask::overTimeTick()} will be continuously called until the Arena state has changed
     * to {@link Arena::STATUS_FINISHING}.
     *
     * @return int
     */
    abstract public function getPlayingTime(): int;

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $arena = $this->getArena();

        if (count($arena->getPlayers()) === 0) {
            $arena->getPlugin()->getLogger()->debug('Forced finish to arena ' . $arena->getId());
            $arena->finish();

            $this->getHandler()?->cancel();
            return;
        }

        if ($arena->isRunning()) {
            if ($this->timePassed > $this->time) {
                $this->overTimeTick();
            } else if (!$arena->isOpponentlessGame() &&
                (
                    ($arena instanceof TeamArena && count($arena->getAliveTeams()) <= 1) ||
                    ($arena->getPlugin()->getMinigameTag() !== ServerManager::MM && count($arena->getAlivePlayers()) <= 1)
                )
            ) {
                $this->finishArena();
            } else {
                $this->gameTick();
                $this->timePassed++;
            }
        } elseif ($arena->isFinishing()) {
            $this->finishTick();
            $this->timePassed++;
        }
    }

    /**
     * self-explanatory.
     *
     * @return Arena
     */
    public function getArena(): Arena
    {
        return $this->arena;
    }

    private function finish(): void
    {
        $arena = $this->getArena();
        $arena->setStatus(Arena::STATUS_FINISHING);
        $this->timePassed = 0;

        $this->cleanWorld();
    }

    /**
     * Clears out entities and world garbage tiles.
     */
    public function cleanWorld(): void
    {
        $world = $this->getArena()->getWorld();

        foreach ($world->getEntities() as $entity) {
            if (!$entity instanceof Player) {
                $entity->flagForDespawn();
            }
        }

        foreach ($world->getLoadedChunks() as $chunk) {
            foreach ($chunk->getTiles() as $tile) {
                if ($tile instanceof Chest) {
                    /** @var ChestInventory $inventory */
                    $inventory = $tile->getInventory();
                    $inventory->clearAll();
                }
            }
        }
    }

    /**
     * Perform a specific action during overtime ticks, useful if you have some sort of overtime gameplay.
     * But this function by default will always finishes off the arena even when there is still players
     * playing in the arena.
     *
     * <p>Note: Remember to set the arena state to {@link Arena::STATUS_FINISHING} so that this function will not loop
     * endlessly.
     */
    public function overTimeTick(): void
    {
        $this->finish();
        $this->checkWinners();
    }

    private function checkWinners(): void
    {
        $arena = $this->getArena();

        foreach ($arena->getAlivePlayers() as $player) {
            if ($arena->isWinner($player)) {
                $this->finishPlayer($player);
            } else {
                $arena->addSpectator($player);
            }
        }
    }

    /**
     * Run cosmetics for the player that won the arena, this function will need to be called for THE PLAYER
     * that WINS the game.
     *
     * @param Player $player
     */
    public function finishPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        $player->playSound('random.levelup');

        $this->getArena()->addSpectator($player, true);
        CosmeticHandler::WIN_EFFECTS()->run($player, $player->getLocation());
    }

    /**
     * Finishes off the arena and overwrites should always assign the winners here, this function is NOT intended for continuous use. Make sure that your overridden function
     * follows PSR-1 elements, which is to always set the arena status to {@link Arena::STATUS_FINISHING}.
     */
    public function finishArena(): void
    {
        $this->finish();
        $this->checkWinners();
    }

    /**
     * Main game ticks, your gameplay will be ticked here. It is self-explanatory.
     */
    abstract public function gameTick(): void;

    /**
     * Perform continuous finishing tick until this task has been cancelled, until the task has been cancelled,
     * the arena has completed its game and will be destroyed.
     */
    public function finishTick(): void
    {
        if ($this->timePassed === 5) {
            $this->getArena()->sendStats();
        } elseif ($this->timePassed === 10) {
            $this->getArena()->finish();

            $this->getHandler()?->cancel();
        }
    }
}