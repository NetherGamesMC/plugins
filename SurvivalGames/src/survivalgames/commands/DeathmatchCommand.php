<?php

declare(strict_types=1);

namespace survivalgames\commands;

use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use survivalgames\SGArena;
use survivalgames\SurvivalGames;

class DeathmatchCommand extends Command
{
    /** @var SurvivalGames */
    private SurvivalGames $plugin;

    public function __construct(SurvivalGames $plugin)
    {
        parent::__construct(strtolower($plugin->getMinigameTag()));

        $this->plugin = $plugin;
        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setAliases(["deathmatch", "dmc"]);
        $this->setDescription('SurvivalGames deathmatch command.');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getEssentials()->getPrefix() . '§cThat command can only be run in-game.');

            return false;
        }

        $arena = $this->plugin->getArena($sender);
        if ($arena !== null && !$arena->hasFlags(SGArena::DEATHMATCH_NOT_READY) && $arena->hasFlags(SGArena::DEATHMATCH_VOTES_WAITING) && !$arena->hasFlags(SGArena::DEATHMATCH_RUNNING)) {
            if ($arena->isSpectator($sender)) {
                $sender->sendMessage(TextFormat::RED . "You cannot vote for deathmatch! You died remember?");
            } else {
                $arena->addDeathmatchVote($sender);
            }
        }

        return true;
    }
}