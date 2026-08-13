<?php

declare(strict_types=1);

namespace survivalgames\task;

use libminigames\Arena;
use libminigames\utils\Utils;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\PopSound;
use survivalgames\SGArena;
use survivalgames\SGTypeArena;
use survivalgames\utils\Items;
use survivalgames\utils\StatsData;
use function abs;
use function array_diff;
use function array_values;
use function count;
use function date;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    /** @var int */
    private int $deathmatch = 30;

    public function __construct(Arena $arena)
    {
        parent::__construct($arena);

        // Waiting time for cages to clear out.
        $this->timePassed = -10;
    }

    public function getPlayingTime(): int
    {
        // As the border shrinks, it will shrink until the game midpoint. So we will have to wait for that
        // border to fully shrinks, killing the player and finally ends the game.
        return PHP_INT_MAX;
    }

    public function gameTick(): void
    {
        // Arena tick.
        $arena = $this->getArena();
        $event = $arena->getEventManager();

        $isWaiting = $arena->hasFlags(SGArena::DEATHMATCH_VOTES_WAITING);
        $isDeathmatch = $arena->hasFlags(SGArena::DEATHMATCH_RUNNING);

        $border = $arena->getBorderManager();
        $border->tickBorder();

        if ($this->timePassed < 0) {
            if ($isDeathmatch) {
                $name = 'Deathmatch begins in ';
            } else {
                $name = 'Cages open in ';
            }

            if ($this->timePassed === -1) {
                foreach ($arena->getPlayers() as $player) {
                    /** @var NGPlayer $player */
                    $player->playSound('note.hat', 1, 0.943874);
                }

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . $name . TextFormat::RED . '1 §esecond!');
            } elseif ($this->timePassed >= -3) {
                foreach ($arena->getPlayers() as $player) {
                    /** @var NGPlayer $player */
                    $player->playSound('note.hat', 1, 0.943874);
                }

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . $name . TextFormat::RED . abs($this->timePassed) . ' §eseconds!');
            } elseif ($this->timePassed >= -5) {
                foreach ($arena->getPlayers() as $player) {
                    $arena->getWorld()->addSound($player->getLocation(), new PopSound(), [$player]);
                }

                $this->getArena()->broadcastMessage(TextFormat::YELLOW . $name . TextFormat::YELLOW . abs($this->timePassed) . ' §eseconds!');
            }

            // Attempt to correct player location, this shouldn't happen in the first place!
            $cage = $this->getArena()->getCageManager();
            foreach ($this->getArena()->getAlivePlayers() as $player) {
                /** @var Position $pos */
                $pos = $cage->getCagePosition($player);

                if ($player->getLocation()->distance($pos) >= 5) {
                    $player->teleport($pos);
                }
            }
        } else {
            if ($this->timePassed === 0) {
                if ($isDeathmatch) {
                    $arena->setArenaFlag(SGArena::PLAYERS_INVINCIBLE, false);

                    $border->markAsFinal();
                } else {
                    /** @var NGPlayer $player */
                    foreach ($arena->getAlivePlayers() as $player) {
                        $player->setGamemode(GameMode::SURVIVAL);
                        if ($arena->isNormal()) {
                            $player->setHealthTag();
                        } else {
                            $player->setNameTag("");
                        }
                        $player->setEnergized(false);
                        $player->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
                    }
                }

                $arena->getCageManager()->despawnCages();
                $arena->broadcastMessage('§eCages opened! §cFIGHT!');
            }

            if ($isDeathmatch && !$isWaiting) {
                if (--$this->deathmatch === 0) {
                    $arena->setArenaFlag(SGArena::PLAYERS_INVINCIBLE, true);
                    $arena->getCageManager()->respawnCages($arena->getWorld());
                    $arena->getCageManager()->teleportToCages();

                    $arena->broadcastMessage("§cYou have been teleported to your cages for Deathmatch.");
                    $arena->broadcastMessage("§eDeathmatch starting in §c15§e seconds");

                    $this->timePassed = -15;
                }
            } else {
                if ($this->timePassed === 15) {
                    $arena->setArenaFlag(SGArena::PLAYERS_INVINCIBLE, false);

                    $arena->broadcastMessage(TextFormat::RED . "You are no longer invincible.");
                } else if ($this->timePassed === 30) {
                    $arena->setArenaFlag(SGArena::ALLOW_KILLSTREAK, true);
                } else if (count($this->getArena()->getAlivePlayers()) <= 2) {
                    $arena->setArenaFlag(SGArena::DEATHMATCH_RUNNING, true);
                    $arena->setArenaFlag(SGArena::DEATHMATCH_VOTES_WAITING, false);

                    $arena->broadcastMessage(TextFormat::RED . "2 players left. Deathmatch will start in 30 seconds!");

                    $this->deathmatch = 30;
                } else if ($this->timePassed >= (5 * 60) && $this->timePassed <= (int)(5.5 * 60)) {
                    if ($this->deathmatch === 30) {
                        $arena->setArenaFlag(SGArena::DEATHMATCH_NOT_READY, false);
                        $arena->setArenaFlag(SGArena::DEATHMATCH_VOTES_WAITING, true);

                        $arena->broadcastMessage(TextFormat::GOLD . "Type in /dmc to start voting for deathmatch now!");
                    } else if ($this->deathmatch === 0) {
                        $votes = $arena->checkDeathmatchVotes();
                        if ($votes === SGTypeArena::TRUE) {
                            $arena->setArenaFlag(SGArena::DEATHMATCH_RUNNING, true);
                            $arena->setArenaFlag(SGArena::DEATHMATCH_VOTES_WAITING, false);

                            $arena->broadcastMessage(TextFormat::RED . "Deathmatch will start in 30 seconds!");
                        } elseif ($votes === SGArena::FALSE) {
                            $arena->setArenaFlag(SGArena::DEATHMATCH_NOT_READY, true);
                            $arena->setArenaFlag(SGArena::DEATHMATCH_VOTES_WAITING, false);

                            $arena->broadcastMessage(TextFormat::GRAY . "Not enough votes to start deathmatch!");
                        } else {
                            $arena->broadcastMessage(TextFormat::GRAY . "No one voted for deathmatch! Keep on playing");
                        }

                        $this->deathmatch = 30;
                    }

                    if ($this->deathmatch > 0) {
                        $this->deathmatch--;
                    }
                }

                $this->getArena()->getChestManager()->tickChestTimer($this->timePassed);
                $event->tickEvents($this->timePassed);
            }

            foreach ($this->getArena()->getAlivePlayers() as $player) {
                if ($player->getInventory()->contains(Items::getCompass()) && ($nearPlayer = Utils::getNearestPlayer($player, array_diff($this->getArena()->getAlivePlayers(), [$player]))) !== null) {
                    Utils::sendCompassPosition($player, $nearPlayer->getLocation());
                }
            }
        }

        $this->updateScoreboard();
    }

    /**
     * @return SGArena
     */
    public function getArena(): Arena
    {
        /** @var SGArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function updateScoreboard(): void
    {
        $arena = $this->getArena();
        $players = $arena->getPlayers();
        $scoreboard = $arena->getScoreboard();

        if ($this->timePassed < 0) {
            $scoreboard->setLine($players, 11, CustomIcon::HOURGLASS . TextFormat::GREEN . 'Opens in ' . date('i:s', abs($this->timePassed)));
        } else {
            $isDeathmatch = $arena->hasFlags(SGArena::DEATHMATCH_RUNNING);
            $nextRefill = $arena->getChestManager()->getNextRefill($this->timePassed);
            $suddenDeath = $arena->getBorderManager()->getSuddenDeath();

            $lines = [];

            if ($isDeathmatch && !$arena->hasFlags(SGArena::DEATHMATCH_VOTES_WAITING) && $this->deathmatch > 0) {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . "Deathmatch in " . date('i:s', $this->deathmatch);
            } elseif ($suddenDeath <= 0) {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . "Sudden Death";
            } elseif ($suddenDeath < 60) {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . "Sudden Death in " . date('i:s', $suddenDeath);

                /** @phpstan-ignore-next-line */
            } elseif ($this->timePassed >= 0 && $this->timePassed < 15 && !$isDeathmatch) {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . "Invincible for " . date('i:s', 15 - $this->timePassed);
            } elseif ($nextRefill !== -1) {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . "Refill in " . date('i:s', $nextRefill);
            } else {
                $lines[11] = CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->timePassed);
            }

            if (($event = $arena->getEventManager()->getEventScoreboard($this->timePassed)) !== null) {
                $lines[9] = CustomIcon::WOODEN_CHEST . TextFormat::GREEN . $event;
            } else {
                $lines[9] = CustomIcon::WOODEN_CHEST . TextFormat::GREEN . 'None';
            }

            $scoreboard->setLines($players, $lines);
        }
    }

    public function finishArena(): void
    {
        $arena = $this->getArena();

        $player = array_values($arena->getAlivePlayers())[0] ?? null;
        if ($player !== null) {
            $statsData = $arena->getStatsData();
            $statsData->addValue($player, StatsData::WINS);
            $statsData->addValue($player, StatsData::SG_WINS);

            $player->sendTitle('§l§6VICTORY!', '§7You were the last player standing!', 0, 100, 20);
        }

        parent::finishArena();
    }
}