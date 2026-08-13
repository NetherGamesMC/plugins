<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames;

use libminigames\events\MinigameQuitEvent;
use libminigames\utils\Forms;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function is_numeric;
use function strtolower;

/**
 * A {@link Command} that is responsible in handling and registering commands.
 * You may see that this class explicitly configures command by it nature self.
 *
 * <p>The command label hereby is set from given function, {@link Minigame::getMinigameName()}.
 * Commands can only be executed from given function mentioned earlier.
 *
 * <p>However, during development mode, this command class will come in handy, the method
 * exposes "join" command for developers to join this arena without having a ProxyLink to connect
 * directly to the game. The followings are the list of commands registered by this class:
 *
 * <code>
 *  /example join [Arena mode]  >> Joins to an arena directly.
 *  /example start              >> Starts an arena you're into directly.
 *  /example quit|leave         >> Leaves an arena.
 *  ...
 * </code>
 *
 * <p>Although is true that commands are registered internally, regardless of that, you can create
 * your own command in an abstract function, {@see Command::onCommand())}, that will operates like normal
 * {@see PluginCommand::execute()} class, without <code>$label</code> variable.
 *
 * @package libminigames
 */
abstract class Command extends \pocketmine\command\Command
{
    /** @var Minigame */
    private Minigame $plugin;

    public function __construct(Minigame $plugin)
    {
        $this->plugin = $plugin;

        parent::__construct(strtolower($plugin->getMinigameTag()));

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases([strtolower($this->getPlugin()->getMinigameName())]);
        $this->setDescription($this->getPlugin()->getMinigameName() . ' Command');
    }

    public function getPlugin(): Minigame
    {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->getEssentials()->getPrefix() . '§cThat command can only be run in-game.');

            return false;
        }

        if (isset($args[0])) {
            if ($args[0] === 'quit' || $args[0] === 'leave') {
                if (($arena = $this->getPlugin()->getArena($sender)) === null) {
                    $sender->sendMessage(TextFormat::RED . "You're not in a " . $this->getPlugin()->getMinigameName() . ' game.');
                } else {
                    $arena->removePlayer($sender, MinigameQuitEvent::LEAVE);
                }
            } elseif (NGEssentials::isInDevelopmentMode()) {
                switch ($args[0]) {
                    case 'join':
                        if (isset($args[1])) {
                            $this->getPlugin()->joinArena($sender, is_numeric($args[1]) ? (int)$args[1] : -1);
                        } else {
                            $this->getPlugin()->joinArena($sender);
                        }
                        break;
                    case 'start':
                        if (($arena = $this->getPlugin()->getArena($sender)) !== null) {
                            $arena->start();
                        }
                        break;
                    default:
                        if (array_shift($args) === 'edit' && count($args) > 0) {
                            $this->runCommand($sender, array_shift($args), $args);
                        } else {
                            $sender->sendMessage($this->getUsage());
                        }
                        break;
                }
            } elseif ($args[0] === 'join' && (($serverManager = $this->getPlugin()->getEssentials()->getServerManager())->enableLobbyHandling() || $serverManager->getServerType() === ServerManager::SETUP)) {
                if (count($this->getPlugin()->getModes()) > 1) {
                    Forms::sendReplayMenu($this->getPlugin(), $sender, false);
                } else {
                    $this->getPlugin()->joinArena($sender);
                }
            } else {
                if (array_shift($args) === 'edit' && count($args) > 0) {
                    $this->runCommand($sender, array_shift($args), $args);
                } else {
                    $sender->sendMessage($this->getUsage());
                }
            }
        }
        return false;
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    public function runCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$this->onGlobalCommand($sender, $subCommand, $args)) {
            return $this->onCommand($sender, $subCommand, $args);
        }
        return true;
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    public function onGlobalCommand(Player $sender, string $subCommand, array $args): bool
    {
        switch ($subCommand) {
            case 'settime':
                if (isset($args[0], $args[1])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        $this->getPlugin()->getArenaConfig()->setTime($args[0], $args[1]);
                        $sender->sendMessage(TextFormat::GREEN . 'Time for map ' . TextFormat::WHITE . $args[2] . TextFormat::GREEN . ' is set to ' . TextFormat::WHITE . $args[3]);
                    } else {
                        $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/' . $this->getName() . ' edit settime <arena> <day|noon|sunset|night|midnight|sunrise OR integer>');
                }
                return true;
            case 'settag':
                if (isset($args[0], $args[1])) {
                    if ($this->getPlugin()->getArenaConfig()->isMap($args[0])) {
                        $coloredTag = $this->getPlugin()->getArenaConfig()->setTag($args[0], $args[1]);
                        if (empty($coloredTag)) {
                            $sender->sendMessage(TextFormat::GREEN . 'Tag for map ' . TextFormat::WHITE . $args[2] . TextFormat::GREEN . ' is cleared.');
                        } else {
                            $sender->sendMessage(TextFormat::GREEN . 'Tag for map ' . TextFormat::WHITE . $args[2] . TextFormat::GREEN . ' is set to ' . $coloredTag);
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "This arena doesn't exist.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . '/' . $this->getName() . ' edit settag <arena> <new|revamped|spookified|clear>');
                }
                return true;
        }
        return false;
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param array<string> $args
     * @return bool
     */
    abstract public function onCommand(Player $sender, string $subCommand, array $args): bool;
}