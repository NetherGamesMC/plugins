<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
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

namespace skywars;

use libasyncio\FileCopyAsyncTask;
use libasyncio\FileDeleteAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use libminigames\Team;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function implode;
use function in_array;
use function is_numeric;
use function strtolower;

class SWCommand extends Command
{
    public function __construct(Skywars $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage("/sw edit <list|remove|create|set|luckyblock>");
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission("nethergames.sw.admin")) {
            $sender->sendMessage("You don't have permission to run this command!");
            return false;
        }

        switch ($subCommand) {
            case 'list':
                $sender->sendMessage(TextFormat::GREEN . 'List of all available maps:');
                $sender->sendMessage(TextFormat::GREEN . implode(', ', $this->getPlugin()->getArenaConfig()->getMaps(false)));
                break;
            case 'remove':
                if (!isset($args[0])) {
                    $sender->sendMessage("/sw edit remove <arena>");
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
                    $sender->sendMessage("/sw edit create <arena>");
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
            case 'set':
                if (!isset($args[0], $args[1], $args[2])) {
                    $sender->sendMessage('/sw edit set <arena> <team> <option>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    if (is_numeric($args[1]) && ($teamId = (int)$args[1]) >= 0 && $teamId <= 11) {
                        if (strtolower($args[2]) === 'spawn') {
                            $this->getPlugin()->getArenaConfig()->setTeamSpawn($args[0], $teamId, $sender->getLocation());
                            $sender->sendMessage(TextFormat::GREEN . 'Team ' . Team::TEAMS[$teamId] . '§a spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                        } else {
                            $sender->sendMessage(TextFormat::RED . 'Invalid option, use spawn');
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . 'You must specify a team between 0-7.');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                }
                break;
            case 'luckyblock':
                if (!isset($args[0])) {
                    $sender->sendMessage('/sw edit luckyblock <arena>');
                    return false;
                }
                $arena = $args[0];
                if (in_array($arena, $this->getPlugin()->getArenaConfig()->getMaps(false), true)) {
                    $this->getPlugin()->getArenaConfig()->setLuckyBlockSpawn($arena, $sender->getLocation());
                    $sender->sendMessage(TextFormat::GREEN . 'New LuckyBlock spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
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
     * @return Skywars
     */
    public function getPlugin(): Minigame
    {
        /** @var Skywars $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}