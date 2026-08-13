<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars\tasks;

use bedwars\BWArena;
use bedwars\BWItems;
use bedwars\BWTeam;
use bedwars\shops\Upgrade;
use bedwars\utils\entity\EnderDragon;
use bedwars\utils\Items;
use bedwars\utils\StatsData;
use bedwars\utils\Utils;
use libminigames\Arena;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\tile\Bed;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_filter;
use function count;
use function in_array;
use function str_replace;
use function ucfirst;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask
{
    public function gameTick(): void
    {
        $arena = $this->getArena();
        $world = $arena->getWorld();
        $plugin = $arena->getPlugin();
        $ess = $plugin->getEssentials();
        $playerManager = $ess->getPlayerManager();
        $players = $arena->getPlayers();

        $aliveTeams = $arena->getAliveTeams();
        if ($this->timePassed % 2 === 0) {
            foreach ($aliveTeams as $team) {
                $teamSpawn = $arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, $team->getId());

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
                            if (!$alivePlayer->isSpectator() && $teamSpawn->distance($alivePlayer->getLocation()) < 25 && !$alivePlayer->getEffects()->has(VanillaEffects::WATER_BREATHING())) {
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
                case 6:
                    if (count($diamondGenerators = $arena->getDiamondGenerator()) === 0) {
                        break;
                    }

                    foreach ($diamondGenerators as $generator) {
                        $generator->setTier(2, $typeConverters, $packets);
                    }

                    $arena->broadcastMessage(TextFormat::AQUA . 'Diamond Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' II', true);
                    break;
                case 12:
                    if (count($emeraldGenerators = $arena->getEmeraldGenerator()) === 0) {
                        break;
                    }

                    foreach ($emeraldGenerators as $generator) {
                        $generator->setTier(2, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::DARK_GREEN . 'Emerald Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' II', true);
                    break;
                case 18:
                    if (count($diamondGenerators = $arena->getDiamondGenerator()) === 0) {
                        break;
                    }

                    foreach ($diamondGenerators as $generator) {
                        $generator->setTier(3, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::AQUA . 'Diamond Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' III', true);
                    break;
                case 24:
                    if (count($emeraldGenerators = $arena->getEmeraldGenerator()) === 0) {
                        break;
                    }

                    foreach ($emeraldGenerators as $generator) {
                        $generator->setTier(3, $typeConverters, $packets);
                    }
                    $arena->broadcastMessage(TextFormat::DARK_GREEN . 'Emerald Generators' . TextFormat::YELLOW . ' have been upgraded to Tier' . TextFormat::RED . ' III', true);
                    break;
                case 25:
                    if ($arena->getGameSettings()->isEndless()) {
                        break;
                    }

                    $arena->broadcastMessage(TextFormat::RED . 'All beds will be destroyed in 5 minutes!', true);
                    break;
                case 30:
                    if ($arena->getGameSettings()->isEndless()) {
                        break;
                    }

                    foreach ($world->getLoadedChunks() as $chunk) {
                        foreach ($chunk->getTiles() as $tile) {
                            if ($tile instanceof Bed && !$tile->isClosed()) {
                                $block = $tile->getBlock();
                                if (($block instanceof \pocketmine\block\Bed) && ($team = $arena->getTeamByBed($block)) !== null) {
                                    $team->onBedDestroy();
                                    $team->updateScoreboardEntry();

                                    foreach ($block->getAffectedBlocks() as $block) {
                                        $world->setBlock($block->getPosition(), VanillaBlocks::AIR());
                                    }
                                }
                            }
                        }
                    }

                    $arena->broadcastTitle(TextFormat::BOLD . TextFormat::RED . 'BED DESTROYED!', 'All beds have been destroyed!', -1, 1, -1);
                    break;
                case 40:
                    if ($arena->getGameSettings()->isEndless()) {
                        break;
                    }

                    foreach ($aliveTeams as $team) {
                        $count = 1;
                        if ($team->getUpgradeLevel(Upgrade::DRAGON_BUFF()) > 0) {
                            $count = 2;
                        }

                        for ($x = 0; $x < $count; $x++) {
                            $enderDragon = new EnderDragon(Location::fromObject($world->getSpawnLocation()->add(0, 40, 0), $world), $team);
                            $enderDragon->spawnToAll();
                        }

                        $arena->broadcastMessage(TextFormat::RED . 'SUDDEN DEATH: ' . TextFormat::GOLD . '+' . TextFormat::AQUA . $count . ' ' . $team->getColor() . ucfirst(str_replace('_', ' ', $team->getName())) . ' Dragon!', true);
                    }
                    $arena->broadcastTitle(TextFormat::RED . 'Sudden Death', '', -1, 1, -1);
                    break;
            }

            if (in_array($minutes, [6, 12, 18, 24], true) && !$arena->getGameSettings()->hasFreeItems()) {
                foreach ($typeConverters as $key => $typeConverter) {
                    if (isset($packets[$key])) {
                        NetworkBroadcastUtils::broadcastPackets($converterRecipients[$key], $packets[$key]);
                    }
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
            $player->getXpManager()->setXpAndProgress((int)$secondsLeft, $secondsLeft / BWItems::LEVITATION_DURATION);
        }

        $arena->getPlayerSwapUtils()?->handle($this->timePassed, $arena);

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
     * @return BWArena
     */
    public function getArena(): Arena
    {
        /** @var BWArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function updateScoreboard(): void
    {
        $event = null;
        $time = null;

        if (($gameSettings = $this->getArena()->getGameSettings())->hasFreeItems()) {
            if (!$gameSettings->isEndless()) {
                if ($this->timePassed < ($time = 30 * 60)) {
                    $event = CustomIcon::HOURGLASS . "No beds";
                } elseif ($this->timePassed < ($time = 40 * 60)) {
                    $event = CustomIcon::HOURGLASS . 'Sudden death';
                } else {
                    $event = CustomIcon::HOURGLASS . 'Game end';
                    $time = $this->time;
                }
            }
        } elseif ($this->timePassed < ($time = 6 * 60)) {
            $event = CustomIcon::DIAMOND . 'II';
        } elseif ($this->timePassed < ($time = 12 * 60)) {
            $event = CustomIcon::EMERALD . 'II';
        } elseif ($this->timePassed < ($time = 18 * 60)) {
            $event = CustomIcon::DIAMOND . 'III';
        } elseif ($this->timePassed < ($time = 24 * 60)) {
            $event = CustomIcon::EMERALD . 'III';
        } elseif (!$gameSettings->isEndless()) {
            if ($this->timePassed < ($time = 30 * 60)) {
                $event = CustomIcon::HOURGLASS . "No beds";
            } elseif ($this->timePassed < ($time = 40 * 60)) {
                $event = CustomIcon::HOURGLASS . 'Sudden death';
            } else {
                $event = CustomIcon::HOURGLASS . 'Game end';
                $time = $this->time;
            }
        }

        $arena = $this->getArena();
        $teamCount = count($arena->getTeams());
        if ($arena->isTriosOrSquads()) {
            $line = $teamCount + 8;
        } elseif ($arena->isVersus()) {
            $line = ($arena->getTeamSize() + 1) * count($arena->getTeams()) + 4;
        } else {
            $line = $teamCount + 4;
        }

        if ($event === null || $time === null) {
            $arena->getScoreboard()->setLine($arena->getPlayers(), $line, "Endless Game");
        } else {
            $arena->getScoreboard()->setLine($arena->getPlayers(), $line, $event . ' in ' . date('i:s', $time - $this->timePassed));
        }
    }

    public function overTimeTick(): void
    {
        $this->getArena()->broadcastTitle(TextFormat::RED . 'YOU LOSE!', TextFormat::GOLD . ' You ran out of time!');

        parent::overTimeTick();
    }

    public function finishArena(): void
    {
        $arena = $this->getArena();
        /** @var BWTeam|null $aliveTeam */
        $aliveTeam = $arena->getAliveTeams()[0] ?? null;
        if ($aliveTeam !== null) {
            foreach ($aliveTeam->getPlayers() as $player) {
                if ($arena->isSoloGame()) {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last player standing!', 0, 100, 20);
                } else {
                    $player->sendTitle('§l§6VICTORY!', '§7You were the last team standing!', 0, 100, 20);
                }
            }
        }

        $statsData = $arena->getStatsData();
        foreach ($arena->getTeams() as $team) {
            foreach ($team->getXuids() as $xuid) {
                if ($team === $aliveTeam) {
                    if (!$arena->isVersus()) {
                        $statsData->addValue($xuid, StatsData::BW_WINS);
                    }
                    $statsData->addValue($xuid, StatsData::WINS);
                    $statsData->addValue($xuid, StatsData::BW_MODE_WINS);
                } elseif (!$team->isBedAlive()) {
                    $statsData->addValue($xuid, StatsData::DEATHS);
                    $statsData->addValue($xuid, StatsData::BW_DEATHS);
                    $statsData->addValue($xuid, StatsData::BW_MODE_DEATHS);
                }
            }
        }

        parent::finishArena();
    }

    public function getPlayingTime(): int
    {
        return 50 * 60;
    }
}
