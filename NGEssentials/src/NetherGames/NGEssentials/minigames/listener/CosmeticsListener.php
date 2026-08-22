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
 * @author driesboy
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\minigames\listener;

use libminigames\events\player\PlayerKillEvent;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use function array_rand;

/**
 * Handles NetherGames kill cosmetics: generated, localized kill messages and kill effects/sounds.
 */
final class CosmeticsListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerKillEvent $event
     *
     * @priority NORMAL
     */
    public function onKill(PlayerKillEvent $event): void
    {
        $killer = $event->getKiller();
        if ($killer !== null && !$killer->isSpectator()) {
            $location = $killer->getLocation();
            CosmeticHandler::KILL_EFFECTS()->run($killer, $location);
            CosmeticHandler::KILL_SOUNDS()->run($killer, $location);
        }

        $message = $event->getKillMessage();
        if ($message === '{PLAYER} §r§7died.') {
            $event->setMessage($this->randomKillMessage($event->getCause(), $killer !== null));
        }
    }

    /**
     * NetherGames kill-message catalog.
     *
     * @param int $cause An {@link EntityDamageEvent} cause
     * @param bool $tagged Whether there is a damager to substitute into the message.
     */
    private function randomKillMessage(int $cause, bool $tagged): string
    {
        switch ($cause) {
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
            case EntityDamageEvent::CAUSE_PROJECTILE:
                $messages = [
                    '{PLAYER} §r§7was killed by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was struck down by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was filled full of lead by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7met their end by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7died in combat with {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was no match for {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was rekt by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7took the L to {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was smacked by {DAMAGER}§r§7.',
                ];
                break;
            case EntityDamageEvent::CAUSE_LAVA:
                $messages = $tagged ? [
                    '{PLAYER} §r§7was melted by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to dust by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was turned to ash by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was given the cold shoulder by {DAMAGER}§r§7.',
                ] : [
                    '{PLAYER} §r§7burned to death.',
                    '{PLAYER} §r§7died in fire.',
                ];
                break;
            case EntityDamageEvent::CAUSE_VOID:
                $messages = $tagged ? [
                    '{PLAYER} §r§7was knocked into the void by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was knocked off the edge by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was delivered into nothingness by {DAMAGER}§r§7.',
                ] : [
                    '{PLAYER} §r§7fell into the void.',
                    '{PLAYER} §r§7fell into nothingness.',
                ];
                break;
            case EntityDamageEvent::CAUSE_FALL:
                $messages = $tagged ? [
                    '{PLAYER} §r§7was knocked off a cliff by {DAMAGER}§r§7.',
                    '{PLAYER} §r§7was knocked off the edge by {DAMAGER}§r§7.',
                ] : [
                    '{PLAYER} §r§7fell to their death.',
                    '{PLAYER} §r§7fell off a cliff.',
                ];
                break;
            default:
                $messages = [
                    '{PLAYER} §r§7died.',
                ];
                break;
        }

        return $messages[array_rand($messages)];
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}