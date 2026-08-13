<?php
declare(strict_types=1);

namespace lobby\command;

use lobby\Lobby;
use NetherGames\NGEssentials\commands\BaseCommand;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;

class RangeCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct("range", NGEssentials::getInstance());

        $this->setPermission(Permissions::RANK_OWNER);
        $this->setPermissionMessage("command.reserved.estaff");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!($sender instanceof NGPlayer)) {
            return false;
        }

        foreach (Lobby::getInstance()->getFeaturesManager()->getShootingRanges() as $range) {
            foreach ($range as $shootingRange) {
                $shootingRange->addPlayer($sender);
                $sender->sendMessage("Added");
            }
        }

        return true;
    }
}