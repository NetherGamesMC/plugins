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
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function basename;
use function glob;
use const GLOB_ONLYDIR;

class LsCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('ls', $plugin);

        $this->setPermission(Permissions::RANK_TESTER);
        $this->setPermissionMessage('command.reserved.staff');
        $this->setDescription("Command used for listing all the worlds on the server.");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            /** @var list<string>|false $glob */
            $glob = glob($this->getPlugin()->getServer()->getDataPath() . '/worlds/*', GLOB_ONLYDIR);
            if ($glob === false) {
                $sender->sendMessage('§cCould not read worlds directory');
                return false;
            }

            $worldManager = $this->getPlugin()->getServer()->getWorldManager();
            $worlds = [];

            foreach ($glob as $path) {
                $worldName = basename($path);

                if ($worldManager->isWorldLoaded($worldName)) {
                    if ($worldName === $worldManager->getDefaultWorld()?->getFolderName()) {
                        $worlds[] = TextFormat::RED . $worldName . ' (Default)';
                    } else {
                        $worlds[] = TextFormat::GREEN . $worldName . ' (Loaded)';
                    }
                } else {
                    $worlds[] = TextFormat::GRAY . $worldName;
                }
            }

            $sender->sendMessage(TextFormat::GREEN . 'Worlds: ' . TextFormat::EOL . implode(TextFormat::EOL, $worlds));
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}