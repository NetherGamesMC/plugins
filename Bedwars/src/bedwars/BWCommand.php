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

namespace bedwars;

use libasyncio\FileCopyAsyncTask;
use libasyncio\FileDeleteAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function is_numeric;
use function strtolower;
use function ucfirst;

final class BWCommand extends Command
{
    public function __construct(Bedwars $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage('/bw edit <list|remove|create|get|generator|set>');
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission('nethergames.bw.admin')) {
            $sender->sendMessage("You don't have permission to use this command!");
            return false;
        }

        switch ($subCommand) {
            case 'list':
                $sender->sendMessage(TextFormat::GREEN . 'List of all available maps:');
                $sender->sendMessage(TextFormat::GREEN . implode(', ', $this->getPlugin()->getArenaConfig()->getMaps(false)));
                break;
            case 'remove':
                if (!isset($args[0])) {
                    $sender->sendMessage('/bw edit remove <arena>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    $this->getPlugin()->getArenaConfig()->removeArena($args[0]);
                    $this->getPlugin()->getServer()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(Path::join($this->getPlugin()->getDataFolder(), 'arenas', $args[0])));
                    $sender->sendMessage(TextFormat::GREEN . "Removed map " . $args[0]);
                } else {
                    $sender->sendMessage(TextFormat::RED . "Map " . $args[0] . " not found");
                }
                break;
            case 'create':
                if (!isset($args[0])) {
                    $sender->sendMessage('/bw edit create <arena>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    $sender->sendMessage(TextFormat::RED . 'This arena already exists.');
                } elseif ($sender->getWorld() === $sender->getServer()->getWorldManager()->getDefaultWorld()) {
                    $sender->sendMessage(TextFormat::RED . "You can't setup maps in the lobby.");
                } else {
                    $world = $sender->getWorld();
                    $world->save(true);

                    $folderName = $world->getFolderName();
                    $sender->getServer()->getWorldManager()->unloadWorld($world);

                    $this->getPlugin()->getServer()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $folderName), Path::join($this->getPlugin()->getDataFolder(), 'arenas', $args[0]), function () use ($sender, $folderName, $args) {
                        $this->getPlugin()->getArenaConfig()->createArena($args[0]);

                        if ($sender->isConnected()) {
                            $sender->sendMessage(TextFormat::GREEN . 'Successfully created ' . $args[0]);

                            $worldManager = $sender->getServer()->getWorldManager();
                            $worldManager->loadWorld($folderName);

                            if (($world = $worldManager->getWorldByName($folderName)) !== null) {
                                $sender->teleport($world->getSpawnLocation());
                            }
                        }
                    }));
                }
                break;
            case 'generator':
                if (!isset($args[0], $args[1], $args[2])) {
                    $sender->sendMessage('/bw edit generator <arena> <diamond|emerald> <id>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    if (($generator = strtolower($args[1])) === 'diamond' || $generator === 'emerald') {
                        /** @phpstan-ignore-next-line */
                        if (is_numeric(($id = (int)$args[2])) && $id >= 0 && $id <= 3) {
                            $this->getPlugin()->getArenaConfig()->setGenerator($args[0], $args[1], $id, $sender->getLocation());
                            $sender->sendMessage(TextFormat::GREEN . ucfirst($args[1]) . '-' . $args[2] . ' set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
                        } else {
                            $sender->sendMessage(TextFormat::RED . 'You must specify a generator between 0-3.');
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . 'The generator must be diamond or emerald.');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                }
                break;
            case 'border':
                if (!isset($args[0])) {
                    $sender->sendMessage(TextFormat::RED . '/bw edit border <arena> [expansion=15]');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    $expansion = isset($args[1]) ? intval($args[1]) : 15;

                    if ($expansion < 15) {
                        $sender->sendMessage(TextFormat::RED . "World border expansion must be larger than 15 blocks squared.");
                    } else {
                        $bb = $this->getPlugin()->getArenaConfig()->generateBorderConfig($args[0], $expansion) ?? throw new InvalidCommandSyntaxException("Invalid map name or expansion size given. Please try again.");

                        $sender->sendMessage(TextFormat::GREEN . 'World border is automatically set to ' . '§6X1:§b ' . $bb->minX . ' §6Y1:§b ' . $bb->minY . ' §6Z1:§b ' . $bb->minZ . '§6X2:§b ' . $bb->maxX . ' §6Y2:§b ' . $bb->maxY . ' §6Z2:§b ' . $bb->maxZ);
                        $sender->sendMessage(TextFormat::YELLOW . "World border are determined by the spawn locations and positions of both emerald and diamond generator, please setup this first before running this command if you feel the positions set are incorrect.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                }
                break;
            case 'set':
                if (!isset($args[0], $args[1], $args[2])) {
                    $sender->sendMessage('/bw edit set <arena> <team> <option>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    if (is_numeric($args[1]) && ($teamId = (int)$args[1]) >= 0 && $teamId <= 11) {
                        switch (strtolower($args[2])) {
                            case 'spawn':
                                $this->getPlugin()->getArenaConfig()->setTeamSpawn($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . BWTeam::TEAMS[$teamId] . '§a spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                                break;
                            case 'generator':
                                $this->getPlugin()->getArenaConfig()->setTeamGenerator($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . BWTeam::TEAMS[$teamId] . '§a generator set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
                                break;
                            case 'shop':
                                $this->getPlugin()->getArenaConfig()->setTeamShop($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . BWTeam::TEAMS[$teamId] . '§a shop set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                                break;
                            case 'upgrader':
                                $this->getPlugin()->getArenaConfig()->setTeamUpgrader($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . BWTeam::TEAMS[$teamId] . '§a upgrader set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                                break;
                            default:
                                $sender->sendMessage(TextFormat::RED . 'Invalid option, use spawn, generator, shop or upgrader');
                                break;
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . 'You must specify a team between 0-11.');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                }
                break;
            default:
                $sender->sendMessage($this->getUsage());
                break;
        }

        return true;
    }


    /**
     * @return Bedwars
     */
    public function getPlugin(): Minigame
    {
        /** @var Bedwars $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}