<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\commands;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class WorldTPCommand extends BaseCommand
{

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('worldtp', $plugin);

        $this->setPermission(Permissions::RANK_TESTER);
        $this->setPermissionMessage('command.reserved.staff');
        $this->setAliases(['wp']);
        $this->setDescription('Command used for teleporting to other worlds');
        $this->setUsage('/worldtp <worldname>');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                $worldName = $args[0];
                $worldManager = $this->getPlugin()->getServer()->getWorldManager();

                if ($worldManager->loadWorld($worldName)) {
                    /** @var World $world */
                    $world = $worldManager->getWorldByName($worldName);

                    $previousGamemode = $sender->getGamemode();
                    $sender->teleport($world->getSpawnLocation());
                    if ($sender->setGamemode($previousGamemode) && $previousGamemode === GameMode::CREATIVE) {
                        $sender->setAllowFlight(true);
                        $sender->setFlying(true);
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "That world doesn't exist.");
                }
            } else {
                throw new InvalidCommandSyntaxException();
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}