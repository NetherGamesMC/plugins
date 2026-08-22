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

use libminigames\events\player\PlayerQuitEvent;
use libminigames\utils\RewardEntry;
use libminigames\utils\StatsData;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_pop;
use function ceil;
use function count;
use function implode;
use function in_array;
use function number_format;
use function strval;
use function time;

/**
 * Applies the NetherGames reward engine (XP/credits/coins/tier/weekend/vote boosts, guild XP and
 * crate keys) whenever a player leaves an arena mid-game or at the end of the match.
 */
final class RewardsListener implements Listener
{
    public function __construct(private NGEssentials $plugin)
    {
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $arena = $event->getArena();
        $player = $event->getPlayer();

        if (!$arena->isRunning() && !$arena->isFinishing()) {
            return;
        }
        if ($arena->isPartyGame()) {
            return;
        }
        if ($event->getReason() === PlayerQuitEvent::DISCONNECT_KICK) {
            return;
        }
        if (!in_array($player, $arena->getPlayers(false), true)) {
            return;
        }

        $this->award($arena, $player);
    }

    private function award(\libminigames\Arena $arena, Player $player): void
    {
        $playerData = $this->plugin->getPlayerData();
        $playerManager = $this->plugin->getPlayerManager();
        $stats = $arena->getStatsData();
        $won = $arena->isWinner($player);

        $entries = $arena->getRewards($player);

        $player->sendMessage("§e§lREWARD SUMMARY:");

        // --- XP ---
        $totalXp = 0;
        $xpMultiplier = 1;
        $xpMultiplierReasons = [];

        $totalXp += ceil($stats->getValue($player, StatsData::KILLS) / 2);
        $totalXp += $stats->getValue($player, StatsData::PERFECT_SHOTS) * 3;

        foreach ($entries as $entry) {
            if ($entry->getType() === 'xp') {
                $totalXp += $entry->getAmount();
                if ($entry->getAmount() > 0 && $entry->getLabel() !== '') {
                    $player->sendMessage(CustomIcon::EXPERIENCE . '+' . $entry->getAmount() . ' XP (' . $entry->getLabel() . ')');
                }
            }
        }

        if ($won) {
            $totalXp += 9;
        }

        if (Utils::isWeekend()) {
            $xpMultiplierReasons[] = "the double XP weekend";
            $xpMultiplier *= 2;
        }

        $bestRankBoost = 0;
        $bestRankLabel = null;
        foreach ($arena->getPlayers(false) as $ingamePlayer) {
            $boost = $this->rankXpBoost($ingamePlayer);
            if ($boost > $bestRankBoost) {
                $bestRankBoost = $boost;
                $bestRankLabel = $this->rankBoostLabel($boost);
            }
            if ($bestRankBoost === 4) {
                break;
            }
        }

        if ($bestRankBoost > 0 && $bestRankLabel !== null) {
            $xpMultiplierReasons[] = "a $bestRankLabel player in-game";
            switch ($bestRankBoost) {
                case 4:
                    $xpMultiplier *= 3;
                    break;
                case 3:
                    $xpMultiplier *= 2.5;
                    break;
                case 2:
                    $xpMultiplier *= 2;
                    break;
                default:
                    $xpMultiplier *= 1.5;
                    break;
            }
        }

        if ($player->hasPermission(Permissions::TIER_DIAMOND)) {
            $xpMultiplierReasons[] = "your Diamond tier";
            $xpMultiplier *= 2;
        } elseif ($player->hasPermission(Permissions::TIER_SAPPHIRE)) {
            $xpMultiplierReasons[] = "your Sapphire tier";
            $xpMultiplier *= 1.75;
        } elseif ($player->hasPermission(Permissions::TIER_AMETHYST)) {
            $xpMultiplierReasons[] = "your Amethyst tier";
            $xpMultiplier *= 1.5;
        } elseif ($player->hasPermission(Permissions::TIER_OPAL)) {
            $xpMultiplierReasons[] = "your Opal tier";
            $xpMultiplier *= 1.25;
        } elseif ($player->hasPermission(Permissions::TIER_GOLD)) {
            $xpMultiplierReasons[] = "your Gold tier";
            $xpMultiplier *= 1.1;
        } elseif ($player->hasPermission(Permissions::TIER_SILVER)) {
            $xpMultiplierReasons[] = "your Silver tier";
            $xpMultiplier *= 1.05;
        }

        if ((time() - $playerData->getInt($player, PlayerData::VOTE_TIME)) < (60 * 60 * 24)) {
            $xpMultiplierReasons[] = "your voting status";
            $xpMultiplier *= 1.25;
        }

        if ($arena->isFinishing() || $arena->isSpectator($player)) {
            $totalXp++;
        }

        if ($totalXp > 0) {
            $totalXp = (int)ceil($totalXp * $xpMultiplier);
            $playerData->addInt($player, PlayerData::XP, $totalXp);
            $player->sendMessage(CustomIcon::EXPERIENCE . '+' . $totalXp . ' XP');
        }

        // --- Credits ---
        $creditsMultiplier = 1;
        $creditsMultiplierReasons = [];
        $totalCredits = 0;
        foreach ($entries as $entry) {
            if ($entry->getType() === 'credits') {
                $totalCredits += $entry->getAmount();
                if ($entry->getAmount() > 0 && $entry->getLabel() !== '') {
                    $player->sendMessage($entry->getLabel() . ' §r§7→ §e+' . $entry->getAmount() . ' Credits');
                }
            }
        }

        if ($totalCredits > 0) {
            if ((time() - $playerData->getInt($player, PlayerData::VOTE_TIME)) < (60 * 60)) {
                $creditsMultiplierReasons[] = "your voting status";
                $creditsMultiplier *= 2;
            }

            $totalCredits = (int)ceil($totalCredits * $creditsMultiplier);
            $playerData->addInt($player, PlayerData::STATUS_CREDITS, $totalCredits);
            $player->sendMessage(CustomIcon::MYSTIC_CHEST . '+' . $totalCredits . ' Credits');
        }

        // --- Coins ---
        $totalCoins = 0;
        foreach ($entries as $entry) {
            if ($entry->getType() === 'coins') {
                $totalCoins += $entry->getAmount();
                if ($entry->getAmount() > 0 && $entry->getLabel() !== '') {
                    $player->sendMessage($entry->getLabel() . ' §r-> §+' . $entry->getAmount() . ' Coins');
                }
            }
        }

        if ($totalCoins > 0) {
            $playerData->addInt($player, PlayerData::COINS, $totalCoins);
            $player->sendMessage(CustomIcon::COIN . '+' . $totalCoins . ' Coins');
        }

        // --- Guild XP ---
        if ($won && ($guild = $playerManager->getSocialManager()->getGuildsManager()->getGuild($playerData->getInt($player, PlayerData::GUILD))) !== null) {
            if ($guild->isDisabled()) {
                $player->sendMessage(CustomIcon::SHIELD . ' ' . TextFormat::RED . "Guild Disabled");
            } else {
                $winXp = $arena->getGXP();
                $player->sendMessage(CustomIcon::SHIELD . '+' . $winXp . " Guild XP");
                $guild->addXp($winXp);
            }
        }

        // --- Crate key ---
        if ($won && $playerManager->getCosmeticHandler()->shouldGiveCrateKey($player)) {
            $player->sendMessage(CustomIcon::KEY . '+1 Crate Key');
            $playerData->addInt($player, PlayerData::KEYS, 1);
        }

        if (count($xpMultiplierReasons) > 0) {
            $player->sendMessage(TextFormat::GREEN . "Your XP total includes a " . TextFormat::BOLD . TextFormat::YELLOW . number_format($xpMultiplier, 2) . TextFormat::RESET . TextFormat::GREEN . " boost thanks to " . $this->prettyList($xpMultiplierReasons));
        }
        if (count($creditsMultiplierReasons) > 0) {
            $player->sendMessage(TextFormat::GREEN . "Your credits total includes a " . TextFormat::BOLD . TextFormat::YELLOW . number_format($creditsMultiplier, 2) . TextFormat::RESET . TextFormat::GREEN . " boost thanks to " . $this->prettyList($creditsMultiplierReasons));
        }
    }

    /**
     * The XP-boost tier granted for the most valuable ranked player present in the arena. Ported
     * from the legacy {@see \libminigames\Arena::calculateXpBoost()}.
     *
     * @param \pocketmine\player\Player $player
     * @return int 0 = none, 1 = Ultra, 2 = Emerald, 3 = Legend, 4 = Titan
     */
    private function rankXpBoost(\pocketmine\player\Player $player): int
    {
        if ($player->hasPermission(Permissions::RANK_TITAN)) {
            return 4;
        }
        if ($player->hasPermission(Permissions::RANK_LEGEND)) {
            return 3;
        }
        if ($player->hasPermission(Permissions::RANK_EMERALD)) {
            return 2;
        }
        if ($player->hasPermission(Permissions::RANK_ULTRA)) {
            return 1;
        }

        return 0;
    }

    private function rankBoostLabel(int $boost): string
    {
        return match ($boost) {
            4 => 'Titan',
            3 => 'Legend',
            2 => 'Emerald',
            default => 'Ultra',
        };
    }

    /**
     * @param string[] $values
     */
    private function prettyList(array $values): string
    {
        $last = array_pop($values);
        $output = implode(', ', $values);
        if ($output !== '') {
            $output .= ' and ';
        }
        $output .= $last;

        return $output;
    }

    public function getPlugin(): NGEssentials
    {
        return $this->plugin;
    }
}