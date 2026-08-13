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

namespace murdermystery;

use libasyncio\FileCopyAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function implode;
use function in_array;
use function strtolower;

class MMCommand extends Command
{
    public function __construct(MurderMystery $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage('/mm edit <list|create|set>');
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission('nethergames.mm.admin')) {
            $sender->sendMessage("You don't have permission to run this command!");
            return false;
        }

        /** @var MurderMystery $plugin */
        $plugin = $this->getPlugin();

        switch (strtolower($subCommand)) {
            case 'list':
                $sender->sendMessage(TextFormat::GREEN . 'List of all available maps:');
                $sender->sendMessage(TextFormat::GREEN . implode(', ', $this->getPlugin()->getArenaConfig()->getMaps(false)));
                break;
            case 'create':
                if (!isset($args[0])) {
                    $sender->sendMessage('/mm edit create <arena>');
                    return false;
                }
                if (in_array($args[0], $plugin->getArenaConfig()->getMaps(false), true)) {
                    $sender->sendMessage(TextFormat::RED . 'That arena already exists.');
                } elseif ($sender->getWorld() === $sender->getServer()->getWorldManager()->getDefaultWorld()) {
                    $sender->sendMessage(TextFormat::RED . "You can't setup maps in the lobby.");
                } else {
                    $world = $sender->getWorld();
                    $world->save(true);

                    $folderName = $world->getFolderName();
                    $sender->getServer()->getWorldManager()->unloadWorld($world);

                    $plugin->getServer()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Path::join($plugin->getServer()->getDataPath(), 'worlds', $folderName), Path::join($plugin->getDataFolder(), 'arenas', $args[0]), function () use ($sender, $plugin, $folderName, $args) {
                        $plugin->getArenaConfig()->createArena($args[0]);

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
                    $sender->sendMessage(TextFormat::RED . '/mm edit set <arena> <team> <option>');
                    return false;
                }
                if (in_array($args[0], $plugin->getArenaConfig()->getMaps(false), true)) {
                    $loc = $sender->getLocation();
                    switch ($args[1]) {
                        case 'resource':
                            $plugin->getArenaConfig()->setResourceSpawn($args[0], $loc, (int)$args[2]);
                            $sender->sendMessage(TextFormat::GREEN . 'Resource spawn §6' . $args[2] . '§a set to ' . '§6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ());
                            break;
                        case 'spawn':
                            $plugin->getArenaConfig()->setSpawn($args[0], $loc, (int)$args[2]);
                            $sender->sendMessage(TextFormat::GREEN . 'Players spawn §6' . $args[2] . '§a set to ' . '§6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ() . ' §6Yaw:§b ' . $loc->getYaw() . ' §6Pitch:§b ' . $loc->getPitch());
                            break;
                        default:
                            $sender->sendMessage(TextFormat::RED . 'Invalid option, use resource or spawn');
                            break;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "That arena doesn't exist.");
                }
                break;
            default:
                $sender->sendMessage($this->getUsage());
                break;
        }
        return true;
    }

    /**
     * @return MurderMystery
     */
    public function getPlugin(): Minigame
    {
        /** @var MurderMystery $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}