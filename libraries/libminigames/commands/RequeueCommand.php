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

namespace libminigames\commands;

use libminigames\Minigame;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * A standalone command for instantly requeuing players into the same game mode
 * after being eliminated or when the game has ended.
 *
 * @package libminigames
 */
class RequeueCommand extends \pocketmine\command\Command
{
    private Minigame $plugin;

    public function __construct(Minigame $plugin)
    {
        $this->plugin = $plugin;

        parent::__construct('requeue');

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setDescription('Instantly requeue into another game');
    }

    public function getPlugin(): Minigame
    {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getEssentials()->getPrefix() . '§cThat command can only be run in-game.');
            return false;
        }

        if (($arena = $this->plugin->getArena($sender)) === null) {
            $sender->sendMessage(TextFormat::RED . "You're not in a " . $this->plugin->getMinigameName() . ' game.');
            return false;
        }

        if (!$arena->isSpectator($sender)) {
            $sender->sendMessage(TextFormat::RED . "You can only use this command after being eliminated or when the game has ended.");
            return false;
        }

        if ($sender instanceof NGPlayer) {
            $partyManager = $this->plugin->getEssentials()->getPlayerManager()->getSocialManager()->getPartyManager();
            $party = $partyManager->getParty($sender);
            if ($party !== null && $party->getLeader() !== $sender) {
                $sender->sendMessage(TextFormat::RED . "Only your party leader can requeue the party into a new game.");
                return false;
            }
        }
        $mode = $this->plugin->getModes()[$arena->getModeId()];
        $this->plugin->requeuePlayer($sender, $arena, $mode);

        return true;
    }
}
