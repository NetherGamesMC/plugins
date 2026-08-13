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
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PingCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('ping', $plugin);

        $this->setPermission(Permissions::DEFAULT_COMMAND_PERMISSION);
        $this->setDescription('See your or someone else\'s ping');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
            return true;
        }

        $playerManager = $this->getPlugin()->getPlayerManager();

        if (isset($args[0])) {
            $player = $playerManager->getBestMatchingPlayer($args[0]);
        } else {
            $player = $sender;
        }

        $playerName = $player->getName();
        $displayName = $playerName;
        if ($player instanceof NGPlayer) {
            $displayName = $playerManager->getPlayerName($player);
            [$downstream, $upstream] = $player->getLatencyData();

            if ($player->getName() === $sender->getName()) {
                $sender->sendMessage("§6Your ping to the server is currently " . PingCommand::parseColoredPing($downstream + $upstream) . " §6(Upstream: " . PingCommand::parseColoredPing($upstream) . "§6, Downstream: " . PingCommand::parseColoredPing($downstream) . "§6).");
            } else {
                $sender->sendMessage("§6" . $displayName . "'s ping to the server is currently " . PingCommand::parseColoredPing($downstream + $upstream) . " §6(Upstream: " . PingCommand::parseColoredPing($upstream) . "§6, Downstream: " . PingCommand::parseColoredPing($downstream) . "§6).");
            }
        } else {
            $sender->sendMessage("§cThe player §e" . $displayName . "§c is currently not online on this server");
        }
        return true;
    }

    public static function parseColoredPing(float $ping): string
    {
        if ($ping >= 500) {
            $color = TextFormat::RED;
        } elseif ($ping >= 250) {
            $color = TextFormat::YELLOW;
        } else {
            $color = TextFormat::GREEN;
        }

        return $color . $ping . "ms";
    }
}