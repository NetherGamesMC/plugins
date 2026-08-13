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

namespace conquests;

use libasyncio\FileCopyAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function implode;
use function is_numeric;
use function strtolower;
use function ucfirst;

class CQCommand extends Command
{

    public function __construct(Conquests $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage('/cq edit <create|get|generator|set>');
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission("nethergames.cq.admin")) {
            $sender->sendMessage("You don't have permission to run this command!");
            return false;
        }

        switch (strtolower($subCommand)) {
            case 'list':
                $sender->sendMessage(TextFormat::GREEN . 'List of all available maps:');
                $sender->sendMessage(TextFormat::GREEN . implode(', ', $this->getPlugin()->getArenaConfig()->getMaps(false)));
                break;
            case 'create':
                if (!isset($args[0])) {
                    $sender->sendMessage("/cq edit create <arena>");
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
                    $sender->sendMessage('/cq edit generator <arena> <diamond|emerald> <id>');
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
            case 'set':
                if (!isset($args[0], $args[1], $args[2])) {
                    $sender->sendMessage(TextFormat::RED . '/cq edit set <arena> <team> <option>');
                    return false;
                }
                if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                    if (is_numeric($args[1]) && ($teamId = (int)$args[1]) >= 0 && $teamId <= 11) {
                        switch (strtolower($args[2])) {
                            case 'spawn':
                                $this->getPlugin()->getArenaConfig()->setTeamSpawn($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . CQTeam::TEAMS[$teamId] . '§a spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                                break;
                            case 'flag':
                                /** @phpstan-ignore-next-line */
                                if (isset($args[3]) && is_numeric(($spawnerId = (int)$args[3]))) {
                                    $vector3 = $sender->getLocation()->asVector3();
                                    $this->getPlugin()->getArenaConfig()->setFlagSpawn($args[0], $teamId, $spawnerId, $vector3);
                                    $sender->sendMessage(TextFormat::GREEN . 'Team ' . CQTeam::TEAMS[$teamId] . '§a flag (id ' . $spawnerId . ') set to ' . '§6X:§b ' . $vector3->getX() . ' §6Y:§b ' . $vector3->getY() . ' §6Z:§b ' . $vector3->getZ());
                                } else {
                                    $sender->sendMessage(TextFormat::RED . 'You must specify a flag id');
                                    $sender->sendMessage(TextFormat::RED . '/cq edit set <arena> <team> flag <id>');
                                }
                                break;
                            case 'generator':
                                $this->getPlugin()->getArenaConfig()->setTeamGenerator($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . CQTeam::TEAMS[$teamId] . '§a generator set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
                                break;
                            case 'shop':
                                $this->getPlugin()->getArenaConfig()->setTeamShop($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . CQTeam::TEAMS[$teamId] . '§a shop set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                                break;
                            case 'upgrader':
                                $this->getPlugin()->getArenaConfig()->setTeamUpgrader($args[0], $teamId, $sender->getLocation());
                                $sender->sendMessage(TextFormat::GREEN . 'Team ' . CQTeam::TEAMS[$teamId] . '§a upgrader set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
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
     * @return Conquests
     */
    public function getPlugin(): Minigame
    {
        /** @var Conquests $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}