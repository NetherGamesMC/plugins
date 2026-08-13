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
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function str_replace;
use function strtolower;

class TransferCommand extends BaseCommand
{
    /** @var string */
    private string $serverType;

    /**
     * @param string[] $customNames
     * @param string[] $permissions
     */
    public function __construct(NGEssentials $plugin, string $serverType, ?array $customNames = null, ?array $permissions = null)
    {
        parent::__construct($customNames[0] ?? $serverType, $plugin);

        if ($permissions === null) {
            $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        } else {
            $this->setPermissions($permissions);
            $this->setPermissionMessage('command.reserved.staff');
        }

        $this->serverType = $serverType;
        $serverName = ServerManager::getName($serverType);

        if (empty($customNames ?? [])) {
            $this->setAliases([str_replace(' ', '', strtolower($serverName))]);
        } else {
            $this->setAliases($customNames);
        }

        $this->setDescription('Command used for transferring to ' . $serverName . ' server');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof Player) {
            $server = ServerManager::BUTTONS[ServerManager::getName($this->serverType)] ?? [];

            if (isset($server['gameTypes'])) {
                $gameTypes = $server['gameTypes'];

                if (count($gameTypes) > 1) {
                    Forms::sendGameTypeSelector($sender, $this->serverType, $this->getPlugin());
                } else {
                    $this->getPlugin()->getPlayerManager()->transferPlayer($sender, $this->serverType, $gameTypes[0]);
                }
            } elseif (isset($server['teleport'])) {
                $sender->teleport($this->getPlugin()->getServerManager()->getSpawn($server['teleport']));
            } else {
                $this->getPlugin()->getPlayerManager()->transferPlayer($sender, $this->serverType);
            }
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}