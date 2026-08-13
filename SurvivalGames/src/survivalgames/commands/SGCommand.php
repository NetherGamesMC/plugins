<?php

declare(strict_types=1);

namespace survivalgames\commands;

use libasyncio\FileCopyAsyncTask;
use libminigames\Command;
use libminigames\Minigame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use survivalgames\SurvivalGames;
use Symfony\Component\Filesystem\Path;

class SGCommand extends Command
{
    public function __construct(SurvivalGames $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage('/sg edit <teleport|midpoint|replace|create|setspawn>');
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission('nethergames.sg.admin')) {
            $sender->sendMessage("You don't have permission to run that command!");
            return false;
        }
        $wm = $sender->getServer()->getWorldManager();
        switch (strtolower($subCommand)) {
            case 'teleport':
                if (isset($args[0])) {
                    if ($wm->loadWorld($args[0])) {
                        $level = $wm->getWorldByName($args[0]);

                        $sender->teleport($level->getSpawnLocation());
                    } else {
                        $sender->sendMessage(TextFormat::RED . "That world doesn't exists");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/sg edit teleport <World>');
                }
                break;
            case 'create':
                if (isset($args[0])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        $sender->sendMessage(TextFormat::RED . 'This arena already exists.');
                    } elseif ($sender->getWorld() === $wm->getDefaultWorld()) {
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
                } else {
                    $sender->sendMessage(TextFormat::RED . '/sg edit create <arena>');
                }
                break;
            case 'midpoint':
                if (isset($args[0])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        $this->getPlugin()->getArenaConfig()->setMidpoint($args[0], $sender->getLocation());
                        $sender->sendMessage(TextFormat::GREEN . 'Midpoint set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ());
                    } else {
                        $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/sg edit midpoint <arena>');
                    $sender->sendMessage(implode(":", $this->getPlugin()->getArenaConfig()->getMaps(false)));
                }
                break;
            case 'border':
                if (isset($args[0], $args[1], $args[2])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        $runtime = explode(" ", $args[1]);
                        $suddenDeath = $args[4];

                        $i = 1;
                        $settings = [];
                        foreach ($runtime as $value) {
                            if (count(explode(";", $value)) !== 2) {
                                $sender->sendMessage(TextFormat::RED . "Not enough configuration for state-$i in first arguments");
                                break;
                            }
                            $settings["state-$i"] = $value;

                            $i++;
                        }

                        if (count(explode(";", $suddenDeath)) !== 3) {
                            $sender->sendMessage(TextFormat::RED . "Not enough configuration for Sudden Death.");
                            break;
                        }
                        $settings['final-round'] = $suddenDeath;

                        $this->getPlugin()->getArenaConfig()->setBorderSettings($args[0], $settings);
                        $sender->sendMessage(TextFormat::GREEN . 'Arena ' . $args[0] . ' border settings set to ' . TextFormat::GOLD . $args[1] . ' ' . $args[2]);
                    } else {
                        $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/sg edit border <arena> <settings>');
                    $sender->sendMessage(TextFormat::YELLOW . "Border settings can be \"34;140 32;43 32;43\" \"32;60;60\", Read arena config file for more info.");
                }
                break;
            case 'setspawn':
                if (isset($args[0], $args[1])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        if (is_numeric($args[1])) {
                            $teamId = (int)$args[1];
                            $this->getPlugin()->getArenaConfig()->setSpawn($args[0], $teamId, $sender->getLocation());
                            $sender->sendMessage(TextFormat::GREEN . 'Spawn ' . $args[1] . '§a spawn set to ' . '§6X:§b ' . $sender->getLocation()->getX() . ' §6Y:§b ' . $sender->getLocation()->getY() . ' §6Z:§b ' . $sender->getLocation()->getZ() . ' §6Yaw:§b ' . $sender->getLocation()->getYaw() . ' §6Pitch:§b ' . $sender->getLocation()->getPitch());
                        } else {
                            $sender->sendMessage(TextFormat::RED . 'You must specify a spawn between 0-24.');
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/sg edit spawn <arena> <spawn>');
                }
                break;
            default:
                $sender->sendMessage($this->getUsage());
                break;
        }

        return true;
    }

    /**
     * @return SurvivalGames
     */
    public function getPlugin(): Minigame
    {
        /** @var SurvivalGames $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}