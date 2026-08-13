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
use NetherGames\NGEssentials\thread\NGThreadPool;
use pocketmine\command\CommandSender;
use Symfony\Component\Filesystem\Path;
use function date;

class DumpNGMemoryCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('dumpngmemory', $plugin);

        $this->setPermission(Permissions::RANK_DEVELOPER);
        $this->setPermissionMessage('command.reserved.estaff');
        $this->setDescription("Dumps the memory");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {

        $path = $args[0] ?? (Path::join($sender->getServer()->getDataPath(), "memory_dumps", date("D_M_j-H.i.s-T_Y")));

        $sender->getServer()->getMemoryManager()->dumpServerMemory($path, 48, 80);
        NGThreadPool::getInstance()->dumpMemory(Path::join($path, 'NG'), 48, 80);


        return true;
    }
}