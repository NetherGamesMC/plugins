<?php

declare(strict_types=1);

namespace lobby\command;

use lobby\features\FeaturesManager;
use NetherGames\NGEssentials\commands\BaseCommand;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AddPresentCommand extends BaseCommand
{
    /** @var FeaturesManager */
    private FeaturesManager $featuresManager;

    public function __construct(FeaturesManager $featuresManager)
    {
        parent::__construct("addpresent", $featuresManager->getNGEssentials());

        $this->featuresManager = $featuresManager;

        $this->setPermission(Permissions::RANK_OWNER);
        $this->setPermissionMessage("command.reserved.estaff");
        $this->setDescription("Command used for adding NPCs");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (!$this->testPermission($sender)) {
                return true;
            }

            if (!isset($args[0])) {
                $sender->sendMessage(TextFormat::GREEN . $this->getUsage());
                return true;
            }

            $position = $sender->getPosition();
            $this->featuresManager->getPresents()?->addPresent((int)$args[0], $position);

            $sender->sendMessage(TextFormat::GREEN . "Present added at position: (x: {$position->getX()}, y: {$position->getY()}, z: {$position->getZ()})");
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "That command can only be run in-game.");
        }

        return true;
    }
}