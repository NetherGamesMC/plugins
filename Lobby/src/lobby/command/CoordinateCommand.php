<?php
declare(strict_types=1);

namespace lobby\command;

use NetherGames\NGEssentials\commands\BaseCommand;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;

class CoordinateCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct("coord", NGEssentials::getInstance());

        $this->setPermission(Permissions::RANK_DEVELOPER);
        $this->setPermissionMessage("command.reserved.estaff");
        $this->setDescription("Command used to identify player position");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $player */
        $player = $sender;

        $player->sendMessage("§a" . $player->getPosition()->getX() . ":" . $player->getPosition()->getY() . ":" . $player->getPosition()->getZ() . " | Yaw: " . $player->getLocation()->getYaw() . " | Pitch: " . $player->getLocation()->getPitch());
    }
}