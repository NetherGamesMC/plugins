<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use libasyncio\FileCopyAsyncTask;
use libasyncio\FileDeleteAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function implode;
use function strtolower;

class BridgeCommand extends Command
{

    public function __construct(TheBridge $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage("/bridge edit <list|remove|create|set>");
    }

    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission("nethergames.bridge.admin")) {
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
                    $sender->sendMessage("/bridge edit remove <arena>");
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
                    $sender->sendMessage("/bridge edit create <arena>");
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
                    $sender->sendMessage("/bridge edit set <arena> <spawn|point> <team>");
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    $team = strtolower($args[2]);

                    if ($team === 'red' || $team === 'blue') {
                        $teamId = $team === 'red' ? BridgeTeam::RED : BridgeTeam::DARK_BLUE;
                        if ($args[1] === 'spawn') {
                            $this->getPlugin()->getArenaConfig()->setTeamSpawn($args[0], $teamId, $sender->getLocation());
                            $sender->sendMessage(TextFormat::GREEN . 'Team ' . BridgeTeam::TEAMS[$teamId] . '§a spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                        } elseif ($args[1] === 'point') {
                            $this->getPlugin()->getArenaConfig()->setTeamPoint($args[0], $team === 'red' ? BridgeTeam::RED : BridgeTeam::DARK_BLUE, $sender->getLocation());
                            $sender->sendMessage(TextFormat::GREEN . 'Team ' . BridgeTeam::TEAMS[$teamId] . '§a generator set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
                        } else {
                            $sender->sendMessage(TextFormat::RED . 'Invalid option, use spawn, point');
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . 'You must specify a team, red or blue');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "This arena doesn't exist yet");
                }
                break;
        }
        return false;
    }

    /**
     * @return TheBridge
     */
    public function getPlugin(): Minigame
    {
        /** @var TheBridge $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}