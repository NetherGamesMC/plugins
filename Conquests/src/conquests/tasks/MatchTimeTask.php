<?php
/**
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

namespace conquests\tasks;

use conquests\CQArena;
use conquests\CQItems;
use conquests\shops\Upgrade;
use conquests\utils\entity\flag\BaseFlagEntity;
use conquests\utils\Items;
use conquests\utils\StatsData;
use conquests\utils\Utils;
use libminigames\Arena;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_filter;
use function array_map;
use function count;
use function in_array;
use function strtoupper;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public function gameTick(): void
    {
        $arena = $this->getArena();
        $plugin = $arena->getPlugin();
        $ess = $plugin->getEssentials();
        $playerManager = $ess->getPlayerManager();
        $players = $arena->getPlayers();
        $aliveTeams = $arena->getAliveTeams();

        if ($arena->finished) {
            $this->finishArena();

            return;
        }

        if ($this->timePassed === 5) {
            foreach ($aliveTeams as $team) {
                $team->spawnFlag();
            }
        } elseif ($this->timePassed % 2 === 0) {
            foreach ($aliveTeams as $team) {
                $teamSpawn = ($arenaConfig = $arena->getPlugin()->getArenaConfig())->getTeamSpawn($arena, $team->getId());

                if ($team->getUpgradeLevel(Upgrade::HEALTH_POOL()) > 0) {
                    foreach ($team->getAlivePlayers() as $player) {
                        if ($player->getLocation()->distance($teamSpawn) < 20) {
                            $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 10 * 20, 0));
                        }
                    }
                }

                $intruders = [];

                foreach ($aliveTeams as $aliveTeam) {
                    if ($aliveTeam !== $team) {
                        foreach ($aliveTeam->getAlivePlayers() as $alivePlayer) {
                            if (
                                !$alivePlayer->isSpectator() &&
                                ($aliveTeam->isNearFlagSpawn($alivePlayer) ||
                                    $teamSpawn->distance($alivePlayer->getLocation()) < 25)
                                && !$alivePlayer->getEffects()->has(VanillaEffects::WATER_BREATHING())
                            ) {
                                $intruders[] = $alivePlayer;
                            }
                        }
                    }
                }

                $trapManager = $team->getTrapManager();
                if (count($intruders) === 0) {
                    if ($trapManager->hasTrapActivated()) {
                        $trapManager->deactivateTrap();
                    }
                } elseif ($trapManager->hasTrapQueued()) {
                    $trapManager->activate($intruders);
                }
            }
        }

        if ($this->timePassed % 60 === 0) {
            $minutes = $this->timePassed / 60;

            /** @phpstan-var array<int, list<ClientboundPacket>> $packets */
            $packets = [];
            [$typeConverters, $converterRecipients] = TypeConverter::sortByConverter($playerManager->getFPSModePlayers($players));

            switch ($minutes) {
                case 5:
                    foreach ($arena->getDiamondGenerator() as $generator) {
                        $generator->setTier(2, $typeConverters, $packets);
                    }

                    $arena->broadcastMessage(TextFormat::AQUA . 'Diamond Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' II', true);
                    break;
                case 10:
                    foreach ($arena->getEmeraldGenerator() as $generator) {
                        $generator->setTier(2, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::DARK_GREEN . 'Emerald Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' II', true);
                    break;
                case 15:
                    foreach ($arena->getDiamondGenerator() as $generator) {
                        $generator->setTier(3, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::AQUA . 'Diamond Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' III', true);
                    break;
                case 20:
                    foreach ($arena->getEmeraldGenerator() as $generator) {
                        $generator->setTier(3, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::DARK_GREEN . 'Emerald Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' III', true);
                    $arena->broadcastMessage(TextFormat::RED . 'Match ends in 5 minutes!', true);
                    break;
            }

            if (in_array($minutes, [5, 10, 15, 20], true)) {
                foreach ($typeConverters as $key => $typeConverter) {
                    NetworkBroadcastUtils::broadcastPackets($converterRecipients[$key], $packets[$key]);
                }
            }
        }

        $floatingTexts = [];

        foreach ($arena->getGlobalGenerators() as $generator) {
            $generator->tick();

            $floatingTexts[] = $generator->getFloatingText();
        }

        $ess->getEntityManager()->sendMetadata($playerManager->unsetFPSPlayers($players), $floatingTexts);

        $levitatingPlayers = array_filter(array_map(function (Player $player) {
            return [$player, $player->getEffects()->get(VanillaEffects::LEVITATION())?->getDuration() ?? 0];
        }, $this->getArena()->getAlivePlayers()), function (array $data) {
            return $data[1] > 0;
        });

        foreach ($levitatingPlayers as [$player, $duration]) {
            $secondsLeft = $duration / 20;
            $player->getXpManager()->setXpAndProgress((int)$secondsLeft, $secondsLeft / CQItems::LEVITATION_DURATION);
        }

        $alivePlayers = $arena->getAlivePlayers();
        foreach ($alivePlayers as $player) {
            if ($player->getInventory()->contains(Items::getCompass()) && ($nearPlayer = Utils::getNearestPlayer($player, array_diff($alivePlayers, $arena->getTeam($player)->getPlayers()))) !== null) {
                Utils::sendCompassPosition($player, $nearPlayer->getLocation());

                $nearPlayerX = $nearPlayer->getPosition()->getX();
                $nearPlayerZ = $nearPlayer->getPosition()->getZ();
                $playerX = $player->getPosition()->getX();
                $playerZ = $player->getPosition()->getZ();

                $distance = strval(intval(sqrt(pow(($nearPlayerX - $playerX), 2) + pow(($nearPlayerZ - $playerZ), 2))));
                $nearPlayerName = $arena->getTeam($nearPlayer)->getColor() . $nearPlayer->getName() . TextFormat::RESET;
                $player->sendActionBarMessage("$nearPlayerName is $distance blocks away");
            }
            /** @var NGPlayer $player */
            if ($player->isInvisible() && $player->isArmorInvisible()) {
                Utils::sendArmour($player, true, $alivePlayers);
            }
        }

        $this->updateScoreboard();
    }

    /**
     * @return CQArena
     */
    public function getArena(): Arena
    {
        /** @var CQArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function finishArena(): void
    {
        $this->assignWinners();
        parent::finishArena();
    }

    public function assignWinners(): void
    {
        $arena = $this->getArena();
        $bestTeam = $arena->getTeamWithHighestScore();
        if ($bestTeam === null) {
            $arena->broadcastTitle(TextFormat::BOLD . TextFormat::YELLOW . 'DRAW!', TextFormat::YELLOW . 'Reached time limit!', 0, 100, 20);
        } else {
            $otherTeam = $arena->getOtherTeam($bestTeam);
            $arena->broadcastTitle(TextFormat::BOLD . $bestTeam->getColor() . strtoupper($bestTeam->getName()) . ' WINS!', TextFormat::BOLD . $bestTeam->getColor() . $bestTeam->getScore() . TextFormat::GRAY . ' - ' . $otherTeam->getColor() . $otherTeam->getScore(), 0, 100, 20);
        }

        $statsData = $arena->getStatsData();
        foreach ($arena->getTeams() as $team) {
            foreach ($team->getXuids() as $xuid) {
                if ($team === $bestTeam) {
                    $statsData->addValue($xuid, StatsData::WINS);
                    $statsData->addValue($xuid, StatsData::CQ_WINS);
                } else {
                    $statsData->addValue($xuid, StatsData::CQ_DEATHS);
                }
            }
        }
    }

    public function updateScoreboard(): void
    {
        $arena = $this->getArena();

        if ($this->timePassed < ($time = 5 * 60)) {
            $event = CustomIcon::DIAMOND . 'II';
        } elseif ($this->timePassed < ($time = 10 * 60)) {
            $event = CustomIcon::EMERALD . 'II';
        } elseif ($this->timePassed < ($time = 15 * 60)) {
            $event = CustomIcon::DIAMOND . 'III';
        } elseif ($this->timePassed < ($time = 20 * 60)) {
            $event = CustomIcon::EMERALD . 'III';
        } else {
            $event = CustomIcon::HOURGLASS . 'Game End';
            $time = $this->time;
            if ($arena->getGameSettings()->isEndless()) {
                $event = CustomIcon::HOURGLASS . 'Nothing';
            }
        }

        $arena->getScoreboard()->setLine($arena->getPlayers(), 12, $event . ' in ' . date('i:s', $time - $this->timePassed));
    }

    public function overTimeTick(): void
    {
        $this->assignWinners();
        parent::overTimeTick();
    }

    public function cleanWorld(): void
    {
        foreach ($this->getArena()->getWorld()->getEntities() as $entity) {
            if ($entity instanceof BaseFlagEntity) {
                $entity->setOwningEntity(null);
            }
        }

        parent::cleanWorld();
    }

    public function getPlayingTime(): int
    {
        return 25 * 60;
    }

    public function getTimePassed(): int
    {
        return $this->timePassed;
    }
}