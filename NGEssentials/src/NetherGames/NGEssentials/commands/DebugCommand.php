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
 * @author cooldogedev
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\commands;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use WeakMap;
use function implode;
use function round;

class DebugCommand extends BaseCommand
{
    /**
     * @var WeakMap<Player, bool>
     */
    private WeakMap $map;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('debug', $plugin);

        $this->map = new WeakMap();
        $this->setPermissions([Permissions::RANK_CREW, Permissions::RANK_DEVELOPER]);
        $this->setPermissionMessage('command.reserved.estaff');
        $this->setDescription('Command used for debugging the current server');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if ($sender instanceof NGPlayer) {
            $isActive = $this->map[$sender] ?? false;

            if ($isActive) {
                $sender->sendMessage("§cDeactivated debug mode");
            } else {
                $sender->sendMessage("§aActivated debug mode");

                $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($sender): void {
                    if (!$sender->isOnline() || !($this->map[$sender] ?? false)) {
                        throw new CancelTaskException();
                    }

                    $server = $this->getPlugin()->getServer();
                    $bandwidthTracker = $server->getNetwork()->getBandwidthTracker();
                    $download = round($bandwidthTracker->getReceive()->getAverageBytes() / 1024, 2) . " kB/s";
                    $upload = round($bandwidthTracker->getSend()->getAverageBytes() / 1024, 2) . " kB/s";
                    [$downstream, $upstream] = $sender->getLatencyData();

                    $sender->sendActionBarMessage(implode("\n", str_replace(
                        ["{tps_current}", "{tps_average}", "{ping}", "{ping_downstream}", "{ping_upstream}", "{network_download}", "{network_upload}"],
                        [$server->getTicksPerSecond() . " (%" . $server->getTickUsage() . ")", $server->getTicksPerSecondAverage() . " (%" . $server->getTickUsageAverage() . ")", (string)($downstream + $upstream), (string)$downstream, (string)$upstream, $download, $upload],
                        [
                            "§eTPS Current: §6{tps_current} §e| TPS Average: §6{tps_average}",
                            "§ePing: §6{ping}ms §e| Downstream=§6{ping_downstream}ms §e| Upstream=§6{ping_upstream}ms",
                            "§eNetwork Download: §6{network_download} §e| Network Upload: §6{network_upload}",
                        ],
                    ))); // An action bar message to avoid conflicting with other messages such as popups and tips.
                }), 20);
            }

            $this->map[$sender] = !$isActive;
        } else {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
        }

        return true;
    }
}
