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

namespace murdermystery\gamemodes\classic\tasks;

use libminigames\Arena;
use libminigames\tasks\MatchTimeTask;
use murdermystery\gamemodes\classic\MMArenaClassic;
use murdermystery\utils\GuardianCurseSound;
use murdermystery\utils\Items;
use murdermystery\utils\Utils;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\item\Bow;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use function count;
use function date;

class MatchTimeClassicTask extends MatchTimeTask
{
    public const MURDERER_SWORD_TIME = 15;

    public function getPlayingTime(): int
    {
        return 5 * 60;
    }

    public function gameTick(): void
    {
        $timePassed = $this->timePassed - self::MURDERER_SWORD_TIME;
        $murderer = $this->getArena()->getMurderer();
        $detective = $this->getArena()->getDetective();
        $innocentCount = count($this->getArena()->getInnocents());

        if ($innocentCount === 0) {
            if ($murderer === null || !$murderer->isConnected()) {
                $this->getArena()->finish();
            } else {
                $this->getArena()->broadcastMessage(TextFormat::YELLOW . 'The murderer, ' . TextFormat::GRAY . $this->getArena()->getPlugin()->getEssentials()->getPlayerManager()->getPlayerName($murderer) . TextFormat::YELLOW . ', has killed everyone!', true);

                foreach ($this->getArena()->getPlayers() as $p) {
                    if ($this->getArena()->isMurderer($p)) {
                        $this->getArena()->setWinners([$p]);

                        $p->sendTitle('§aYOU WIN!', '§6You killed everyone!', 0, 100, 20);
                        $p->sendMessage('§aYOU WIN! §6You killed everyone!');
                        Utils::playSound($p, 'random.levelup', 1, 1);
                    } else {
                        $p->sendTitle('§cYOU LOSE!', '§6The Murderer killed everyone!', 0, 100, 20);
                        $p->sendMessage('§cYOU LOSE! §6The Murderer killed everyone!');
                    }
                }

                $this->finishArena();
            }
        } elseif ($murderer === null || !$murderer->isConnected()) {
            $this->finishArena();
        } elseif ($this->timePassed > self::MURDERER_SWORD_TIME) {
            $timeLeft = $this->time - $this->timePassed;

            if ($this->timePassed === $this->time - 60) {
                $this->getArena()->broadcastTitle('§c60 §eseconds left!', '§eAfter 60s, the Murderer will lose', 0, 60, 20);
            } elseif ($timePassed % 60 === 0) {
                $this->getArena()->broadcastMessage('§eThe game ends in §c' . date('i:s', $timeLeft) . ' §eminutes!', true);
            }

            if (!$this->getArena()->getGameSettings()->hasAlwaysCompass() && $innocentCount === 1 || $timeLeft < 30) {
                if ($murderer->getInventory()->getItem(Items::COMPASS)->isNull()) {
                    foreach ($this->getArena()->getInnocents() as $innocent) {
                        $innocent->sendTitle('§cWatch out!', '§eThe Murderer can find survivors easily!', 0, 60, 20);
                        $innocent->sendMessage('§cWatch out! §eThe Murderer can find survivors easily!');

                        $this->getArena()->getWorld()->addSound($innocent->getLocation(), new GuardianCurseSound(), [$innocent]);
                    }

                    $murderer->sendTitle('§6You got a Compass!', '§eYou can use it to locate remaining players', 0, 60, 20);
                    $murderer->sendMessage('§6You got a Compass! §eYou can use it to locate remaining players');
                    $murderer->getInventory()->setItem(Items::COMPASS, Items::getInnocentLocator());
                }

                if (($nearPlayer = Utils::getNearestPlayer($murderer, $this->getArena()->getInnocents())) !== null) {
                    Utils::sendCompassPosition($murderer, $nearPlayer->getLocation());
                }
            }

            if ($detective !== null && $detective->isConnected()) {
                /** @var Bow $bow */
                $bow = $detective->getInventory()->getItem(Items::DETECTIVE_BOW_SLOT);

                if ($bow->getDamage() > 0) {
                    $repairValue = (int)($bow->getMaxDurability() / 10);

                    if (($damage = $bow->getDamage() - $repairValue) < 0) {
                        $bow->setDamage(0);
                    } else {
                        $bow->setDamage($damage);
                    }

                    $detective->getInventory()->setItem(Items::DETECTIVE_BOW_SLOT, $bow);
                } elseif ($bow->getDamage() === 0 && $detective->getInventory()->getItem(Items::DETECTIVE_ARROW_SLOT)->equals(VanillaItems::AIR())) {
                    $detective->getInventory()->setItem(Items::DETECTIVE_ARROW_SLOT, VanillaItems::ARROW());
                    Utils::playSound($detective, 'random.pop2', 0.4, 7);
                }
            }

            /** @var Sword $sword */
            $sword = $murderer->getInventory()->getItem(Items::MURDERER_SWORD_SLOT);
            if ($sword->equals(Items::getKnife($murderer), false, false) && $sword->getDamage() > 0) {
                $repairValue = (int)($sword->getMaxDurability() / 10);

                if (($damage = $sword->getDamage() - $repairValue) < 0) {
                    $sword->setDamage(0);
                } else {
                    $sword->setDamage($damage);
                }

                $murderer->getInventory()->setItem(Items::MURDERER_SWORD_SLOT, $sword);
            }

            $this->dropResources($timePassed);
        } elseif ($this->timePassed === self::MURDERER_SWORD_TIME) {
            if ($detective !== null && $detective->isConnected()) { //HACK
                $detective->getInventory()->setHeldItemIndex(0);
                $detective->getInventory()->setItem(Items::DETECTIVE_BOW_SLOT, Items::getDetectiveBow());
                $detective->getInventory()->setItem(Items::DETECTIVE_ARROW_SLOT, VanillaItems::ARROW());
            }

            $murderer->getInventory()->setHeldItemIndex(0);
            $murderer->getInventory()->setItem(Items::MURDERER_SWORD_SLOT, Items::getKnife($murderer));

            $this->getArena()->broadcastMessage('§eThe Murderer has received their sword', true);
            $this->getArena()->broadcastSound('note.hat', 2, 0.943874);
        } elseif ($this->timePassed >= self::MURDERER_SWORD_TIME - 5) {
            $this->getArena()->broadcastMessage('§eThe Murderer gets their sword in §c' . (self::MURDERER_SWORD_TIME - $this->timePassed) . ' §eseconds', true);
            $this->getArena()->broadcastSound('note.hat', 1, 0.943874);

            $this->dropResources($timePassed);
        }

        $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), 7, CustomIcon::HOURGLASS . TextFormat::GREEN . date('i:s', $this->time - $this->timePassed));
    }

    /**
     * @return MMArenaClassic
     */
    public function getArena(): Arena
    {
        /** @var MMArenaClassic $arena */
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
            if ($arena->isMurderer($player)) {
                $player->sendTitle('§cYOU LOSE!', '§6You ran out of time!', 0, 100, 20);
                $player->sendMessage('§cYOU LOSE! §6You ran out of time!');
            } else {
                $player->sendTitle('§aYOU WIN!', '§6The Murderer ran out of time!', 0, 100, 20);
                $player->sendMessage('§aYOU WIN! §6The Murderer ran out of time!');
            }
        }
        $arena->setWinners($arena->getInnocents());

        parent::overTimeTick();
    }
}