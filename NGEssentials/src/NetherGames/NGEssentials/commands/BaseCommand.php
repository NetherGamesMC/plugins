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
use NetherGames\NGEssentials\ServerManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

abstract class BaseCommand extends Command
{
    public const UNREGISTERED_COMMANDS = [
        'ban',
        'ban-ip',
        'banlist',
        'clear',
        'defaultgamemode',
        'deop',
        'difficulty',
        'help',
        'kick',
        'kill',
        'me',
        'op',
        'pardon',
        'pardon-ip',
        'particle',
        'plugins',
        'save-all',
        'save-off',
        'save-on',
        'say',
        'seed',
        'spawnpoint',
        'tell',
        'title',
        'transferserver',
        'version',
    ];

    public function __construct(string $name, private NGEssentials $plugin, string $description = "", ?string $usageMessage = null, array $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public static function registerCommands(NGEssentials $ess): void
    {
        $commandMap = $ess->getServer()->getCommandMap();

        foreach (self::UNREGISTERED_COMMANDS as $command) {
            $commandMap->unregister($commandMap->getCommand($command));
        }

        $commandMap->register(ChatCommand::class, new ChatCommand($ess));
        $commandMap->register(ChatfxCommand::class, new ChatfxCommand($ess));
        $commandMap->register(DebugCommand::class, new DebugCommand($ess));
        $commandMap->register(DrainCommand::class, new DrainCommand($ess));
        $commandMap->register(DumpNGMemoryCommand::class, new DumpNGMemoryCommand($ess));
        $commandMap->register(FriendCommand::class, new FriendCommand($ess));
        $commandMap->register(GetPosCommand::class, new GetPosCommand($ess));
        $commandMap->register(GuildCommand::class, new GuildCommand($ess));
        $commandMap->register(InfoCommand::class, new InfoCommand($ess));
        $commandMap->register(KickCommand::class, new KickCommand($ess));
        $commandMap->register(PartyCommand::class, new PartyCommand($ess));
        $commandMap->register(PingCommand::class, new PingCommand($ess));
        $commandMap->register(ReplyCommand::class, new ReplyCommand($ess));
        $commandMap->register(ReportCommand::class, new ReportCommand($ess));
        $commandMap->register(StaffportalCommand::class, new StaffportalCommand($ess));
        $commandMap->register(StatsCommand::class, new StatsCommand($ess));
        $commandMap->register(TellCommand::class, new TellCommand($ess));
        $commandMap->register(TrackCommand::class, new TrackCommand($ess));
        $commandMap->register(VoteCommand::class, new VoteCommand($ess));
        $commandMap->register(WarnCommand::class, new WarnCommand($ess));
        $commandMap->register(WhereAmICommand::class, new WhereAmICommand($ess));
        $commandMap->register(EmojiHelpCommand::class, new EmojiHelpCommand($ess));

        $serverManager = $ess->getServerManager();
        if (NGEssentials::isInDevelopmentMode()) {
            $commandMap->register(ConvertCommand::class, new ConvertCommand($ess));
            $commandMap->register(TestCommand::class, new TestCommand($ess));
        }
        if ($serverManager->getServerType() === ServerManager::SETUP || NGEssentials::isInDevelopmentMode()) {
            $commandMap->register(RmCommand::class, new RmCommand($ess));
            $commandMap->register(LsCommand::class, new LsCommand($ess));
            $commandMap->register(WorldTPCommand::class, new WorldTPCommand($ess));
            $commandMap->register(SaveCommand::class, new SaveCommand($ess));
        }
        if ($serverManager->getServerType() === ServerManager::LOBBY) {
            foreach ($serverManager->getAllServerTypes() as $serverType) {
                if ($serverType !== ServerManager::LOBBY && $serverType !== ServerManager::REPLAY) {
                    $commandMap->register(TransferCommand::class, new TransferCommand($ess, $serverType));
                }
            }
            $commandMap->register(LobbyCommand::class, new LobbyCommand($ess));
            $commandMap->register(HardTransferCommand::class, new HardTransferCommand($ess));
            $commandMap->register(TransferCommand::class, new TransferCommand($ess, ServerManager::SETUP, null, Permissions::STAFF_RANKS));
            $commandMap->register(TransferCommand::class, new TransferCommand($ess, ServerManager::AC));
            $commandMap->register(ReconnectCommand::class, new ReconnectCommand($ess));
        } else if (!$serverManager->isMMOGame()) {
            $commandMap->register(LobbyCommand::class, new LobbyCommand($ess));
        }
    }

    public function testPermission(CommandSender $target, ?string $permission = null): bool
    {
        if ($target instanceof ConsoleCommandSender || $this->testPermissionSilent($target, $permission)) {
            return true;
        }

        $target->sendMessage(KnownTranslationFactory::pocketmine_command_error_permission($this->getName())->prefix(TextFormat::RED));
        return false;
    }

    /**
     * @return NGEssentials
     */
    final public function getPlugin(): Plugin
    {
        return $this->plugin;
    }
}