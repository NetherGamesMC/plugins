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
use function explode;
use function glob;
use const GLOB_ONLYDIR;

class ConvertCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('convert', $plugin);

        $this->setPermission(Permissions::RANK_DEVELOPER);
        $this->setPermissionMessage('command.reserved.estaff');
        $this->setDescription("Command used to convert worlds to the new format");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $server = $this->getPlugin()->getServer();
        $worldManager = $server->getWorldManager();

        /** @var list<string>|false $glob */
        $glob = glob($server->getDataPath() . '/worlds/*', GLOB_ONLYDIR);
        if ($glob === false) {
            $sender->sendMessage('§cCould not read worlds directory');
            return false;
        }

        foreach ($glob as $dir) {
            $name = explode('/worlds/', $dir)[1];

            if (!$worldManager->isWorldLoaded($name)) {
                $worldManager->loadWorld($name, true);

                $world = $worldManager->getWorldByName($name);
                if ($world !== null) {
                    $worldManager->unloadWorld($world);
                }
            }
        }

        return true;
    }
}