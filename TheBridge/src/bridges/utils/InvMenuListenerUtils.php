<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123, larryTheCoder
 *
 */
declare(strict_types=1);

namespace bridges\utils;

use Closure;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use function in_array;

/**
 * Helper utility taken from https://github.com/Muqsit/InvMenuUtils
 *
 * @package bridges\utils
 */
final class InvMenuListenerUtils
{
    /**
     * Assign multiple listeners in order of their priority (the first
     * listener will be called first).
     *
     * @param Closure ...$listeners
     * @return Closure
     */
    public static function multiple(Closure ...$listeners): Closure
    {
        return self::multipleReadWrite(...$listeners);
    }

    public static function multipleReadWrite(Closure ...$listeners): Closure
    {
        return static function (InvMenuTransaction $transaction) use ($listeners): InvMenuTransactionResult {
            foreach ($listeners as $listener) {
                /** @var InvMenuTransactionResult $result */
                $result = $listener($transaction);
                if ($result->cancelled) {
                    self::protectionChecks($transaction->getPlayer());

                    return $transaction->discard();
                }
            }

            return $transaction->continue();
        };
    }

    public static function protectionChecks(Player $player): void
    {
        $item = $player->getCursorInventory()->getItem(0);
        if (!$item->equals(VanillaItems::AIR())) {
            $player->getCursorInventory()->clearAll();

            if (($window = $player->getCurrentWindow()) !== null) {
                $leftovers = $window->addItem($item);

                // Just in case the player inventory is full.
                foreach ($leftovers as $leftover) {
                    $player->getWorld()->dropItem($player->getPosition(), $leftover);
                }
            }
        }
    }

    /**
     * @param int[] $slots
     * @return Closure
     */
    public static function whitelistSlots(array $slots): Closure
    {
        return static function (InvMenuTransaction $transaction) use ($slots): InvMenuTransactionResult {
            if (in_array($transaction->getAction()->getSlot(), $slots, true)) {
                return $transaction->continue();
            }

            return $transaction->discard();
        };
    }
}