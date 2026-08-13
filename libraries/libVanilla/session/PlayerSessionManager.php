<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\session;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class PlayerSessionManager
{
    use SingletonTrait;

    protected static bool $registered = false;
    /**
     * A map of all player XUIDs to their respective PlayerSession objects.
     *
     * @var array<string, PlayerSession>
     */
    protected array $sessions = [];

    public static function register(PluginBase $plugin): void
    {
        if (self::$registered) {
            return;
        }
        $plugin->getServer()->getPluginManager()->registerEvent(
            PlayerQuitEvent::class,
            static function (PlayerQuitEvent $event): void {
                self::getInstance()->delete($event->getPlayer());
            },
            EventPriority::LOWEST, $plugin
        );
        self::$registered = true;
    }

    public function delete(Player $player): void
    {
        if (isset($this->sessions[$player->getXuid()])) {
            unset($this->sessions[$player->getXuid()]);
        }
    }

    public function get(Player $player): PlayerSession
    {
        return $this->sessions[$player->getXuid()] ??= PlayerSession::create($player);
    }
}