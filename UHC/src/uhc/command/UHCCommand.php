<?php
declare(strict_types=1);

namespace uhc\command;

use libminigames\Command;
use libminigames\events\MinigameQuitEvent;
use libminigames\utils\Forms;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\game\UHCArena;
use uhc\UHC;

class UHCCommand extends Command
{
    public function __construct(UHC $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage('/uhc');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return bool
     */
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
                        /** @var UHCArena|null $arena */
                        $arena = $this->getPlugin()->getArena($sender);
                        if ($arena !== null) {
                            $arena->setupMap();
                            $arena->checkTypeVotes();
                            $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($arena): void {
                                $arena->start();
                            }), 5 * 20);
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
            } elseif ($args[0] === 'join' && $this->getPlugin()->getEssentials()->getServerManager()->enableLobbyHandling()) {
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
     * @param string[] $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        return true;
    }
}