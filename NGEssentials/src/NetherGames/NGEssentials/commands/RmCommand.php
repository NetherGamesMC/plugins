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

use libasyncio\FileDeleteAsyncTask;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\thread\NGThreadPool;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function is_dir;

class RmCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('rm', $plugin);

        $this->setPermission(Permissions::RANK_TESTER);
        $this->setPermissionMessage('command.reserved.staff');
        $this->setDescription("Command used for deleting worlds from the worlds folder");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                $worldName = $args[0];
                $worldManager = $this->getPlugin()->getServer()->getWorldManager();

                if (($world = $worldManager->getWorldByName($worldName)) !== null) {
                    $worldManager->unloadWorld($world);
                }

                $origin = Path::join($this->getPlugin()->getServer()->getDataPath(), 'worlds', $worldName);
                if (!is_dir($origin)) {
                    $sender->sendMessage(TextFormat::RED . "The world does not exist!");
                    return true;
                }

                NGThreadPool::getInstance()->submitTask(new FileDeleteAsyncTask($origin, static function () use ($sender, $worldName) {
                    if ($sender->isConnected()) {
                        $sender->sendMessage(TextFormat::GREEN . 'World ' . TextFormat::AQUA . $worldName . TextFormat::GREEN . ' has been deleted from the worlds folder');
                    }
                }));
            } else {
                $sender->sendMessage(TextFormat::RED . '/rm {worldName}');
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}