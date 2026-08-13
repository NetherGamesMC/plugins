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

namespace murdermystery\gamemodes\infection\tasks;

use libminigames\Arena;
use libminigames\tasks\MatchTimeTask;
use murdermystery\gamemodes\infection\MMArenaInfection;
use murdermystery\utils\Items;
use murdermystery\utils\Utils;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\item\Bow;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use function count;
use function date;
use function in_array;

class MatchTimeInfectionTask extends MatchTimeTask
{
    public const INFECTED_CHOOSE_TIME = 15;

    public function getPlayingTime(): int
    {
        return 5 * 60;
    }

    public function gameTick(): void
    {
        $timePassed = $this->timePassed - self::INFECTED_CHOOSE_TIME;
        $timeLeft = $this->time - $this->timePassed;

        if ($this->timePassed === $this->time - 60) {
            $this->getArena()->broadcastTitle('§c60 §eseconds left!', '§eAfter 60s, the Murderer will lose', 0, 60, 20);
        } elseif ($timePassed % 60 === 0) {
            $this->getArena()->broadcastMessage('§eThe game ends in §c' . date('i:s', $timeLeft) . ' §eminutes!', true);
        }

        if ($this->timePassed > self::INFECTED_CHOOSE_TIME) {
            if (count($this->getArena()->getInfected()) === 0) {
                $this->getArena()->setWinners($this->getArena()->getSurvivors());

                foreach ($this->getArena()->getAlivePlayers() as $player) {
                    if ($this->getArena()->isWinner($player)) {
                        $player->sendTitle('§aYOU WIN!', '§6The Infected have been stopped!', 0, 100, 20);
                        $player->sendMessage('§aYOU WIN! §6The Infected have been stopped!');
                        Utils::playSound($player, 'random.levelup', 1, 1);
                    } else {
                        $player->sendTitle(TextFormat::RED . 'The Survivors won!', '', 0, 100, 20);
                    }
                }

                $this->finishArena();
            } elseif (count($this->getArena()->getSurvivors()) === 0) {
                if (($infected = $this->getArena()->getAlpha()) !== null) {
                    $this->getArena()->setWinners([$infected]);
                }

                $this->getArena()->broadcastTitle(TextFormat::RED . 'The Infected won!', '', 0, 100, 20);

                $this->finishArena();
            } else {
                if (!$this->getArena()->getGameSettings()->hasRevealIdentities() && $timeLeft === 30 && count($this->getArena()->getSurvivors()) !== 1) {
                    $this->getArena()->broadcastMessage(TextFormat::GREEN . 'Survivors' . TextFormat::YELLOW . ' have been revealed!', true);

                    foreach ($this->getArena()->getSurvivors() as $survivor) {
                        $survivor->setNameTag($this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getNameTag($survivor, TextFormat::GREEN, true, true));
                    }
                }

                foreach ($this->getArena()->getSurvivors() as $survivor) {
                    /** @var Bow $bow */
                    $bow = $survivor->getInventory()->getItem(Items::SURVIVOR_BOW_SLOT);

                    if ($bow->equals(Items::getBow(), false, false) && $bow->getDamage() > 0) {
                        $repairValue = (int)($bow->getMaxDurability() / 10);

                        if (($damage = $bow->getDamage() - $repairValue) < 0) {
                            $bow->setDamage(0);
                        } else {
                            $bow->setDamage($damage);
                        }

                        $survivor->getInventory()->setItem(Items::SURVIVOR_BOW_SLOT, $bow);
                    }
                }

                foreach ($this->getArena()->getInfected() as $infected) {
                    if ($this->getArena()->getAlpha() === $infected) {
                        if ($this->getArena()->getAlphaDeaths() === 1) {
                            $slot = Items::INFECTED_SWORD_SLOT;
                        } else {
                            $slot = Items::ALPHA_SWORD_SLOT;
                        }

                        if ($infected->isConnected()) {
                            /** @var Sword $sword */
                            $sword = $infected->getInventory()->getItem($slot);

                            if ($sword->equals(Items::getKnife($infected), false, false) && $sword->getDamage() > 0) {
                                $repairValue = (int)($sword->getMaxDurability() / 10);

                                if (($damage = $sword->getDamage() - $repairValue) < 0) {
                                    $sword->setDamage(0);
                                } else {
                                    $sword->setDamage($damage);
                                }

                                $infected->getInventory()->setItem($slot, $sword);
                            }
                        }
                    } else {
                        $resources = $infected->getInventory()->getItem(Items::RESOURCE_SLOT);

                        if ($resources->getCount() < 10) {
                            $infected->sendPopup(TextFormat::RED . 'Collect ' . TextFormat::YELLOW . (10 - $resources->getCount()) . TextFormat::RED . ' more gold to get a sword throw');
                        } else {
                            $infected->sendPopup(TextFormat::GREEN . 'Sword throw available');
                        }
                    }
                }

                $this->dropResources($timePassed);
            }
        } elseif ($this->timePassed === self::INFECTED_CHOOSE_TIME) {
            $alpha = $this->getArena()->getAlpha();
            $alpha ??= $this->getArena()->getPlugin()->getChanceHandler()->getHighestChances($this->getArena())[0];

            if ($alpha === null) {
                $this->finishArena();
            } else {
                if (!in_array($alpha, $this->getArena()->getInfected(), true)) {
                    $this->getArena()->addInfected($alpha);
                }

                $this->getArena()->getScoreboard()->setLines($this->getArena()->getPlayers(), [
                    9 => CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($this->getArena()->getSurvivors()),
                    8 => CustomIcon::ZOMBIE_HEAD . TextFormat::RED . count($this->getArena()->getInfected()),
                ]);

                foreach ($this->getArena()->getAlivePlayers() as $player) {
                    $this->getArena()->assignChance($player);
                    if ($player === $alpha) {
                        $this->getArena()->getScoreboard()->setLine([$player], 13, CustomIcon::GAMEMODE . TextFormat::RED . 'Infected');
                        $player->sendTitle(TextFormat::RED . 'ROLE: ALPHA', TextFormat::YELLOW . 'Secretly infect all players!', 0, 60, 20);

                        $player->getInventory()->setItem(Items::ALPHA_SWORD_SLOT, Items::getKnife($player));
                        $player->getInventory()->setItem(Items::ALPHA_BOW_SLOT, Items::getFakeBow());
                    } else {
                        $player->sendTitle(TextFormat::GREEN . 'ROLE: SURVIVOR', TextFormat::YELLOW . 'Stay alive as long as possible!', 0, 60, 20);

                        $player->getInventory()->setItem(Items::SURVIVOR_BOW_SLOT, Items::getBow());
                        $player->getInventory()->setItem(Items::SURVIVOR_ARROW_SLOT, VanillaItems::ARROW()->setCount(32));
                    }
                    $player->getArmorInventory()->setChestplate(VanillaItems::IRON_CHESTPLATE());
                    $player->getInventory()->setHeldItemIndex(0);
                }
                $this->getArena()->getPlugin()->getChanceHandler()->removeArenaChance($this->getArena());

                $this->getArena()->broadcastMessage('§eThe alpha infected has been chosen!', true);
                $this->getArena()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Watch out! ' . TextFormat::RESET . TextFormat::RED . 'The alpha infected looks like a normal survivor until they have been revealed', true);
                $this->getArena()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Teaming with Infected is not allowed!', true);
                $this->getArena()->broadcastSound('note.hat', 2, 0.943874);
            }
        } elseif ($this->timePassed > self::INFECTED_CHOOSE_TIME - 5) {
            $this->getArena()->broadcastMessage('§eThe alpha infected will be chosen in §c' . (self::INFECTED_CHOOSE_TIME - $this->timePassed) . ' §eseconds!', true);
            $this->getArena()->broadcastSound('note.hat', 1, 0.943874);

            $this->dropResources($timePassed);
        }

        $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 11, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
    }

    /**
     * @return MMArenaInfection
     */
    public function getArena(): Arena
    {
        /** @var MMArenaInfection $arena */
        $arena = parent::getArena();

        return $arena;
    }

    private function dropResources(int $timePassed): void
    {
        if ($timePassed % 7 === 0) {
            for ($i = 1; $i <= 4; ++$i) {
                $this->getArena()->getWorld()->dropItem($this->getArena()->getPlugin()->getArenaConfig()->getRandomResourceSpawn($this->getArena()), Items::getResourceItem());
            }
        }
    }

    public function overTimeTick(): void
    {
        $arena = $this->getArena();

        foreach ($arena->getPlayers() as $player) {
            if ($arena->isInfected($player)) {
                $player->sendTitle('§cYOU LOSE!', '§6You ran out of time!', 0, 100, 20);
                $player->sendMessage('§cYOU LOSE! §6You ran out of time!');
            } else {
                $player->sendTitle('§aYOU WIN!', '§6The Infected ran out of time!', 0, 100, 20);
                $player->sendMessage('§aYOU WIN! §6The Infected ran out of time!');
            }
        }
        $arena->setWinners($arena->getAlivePlayers());

        parent::overTimeTick();
    }
}