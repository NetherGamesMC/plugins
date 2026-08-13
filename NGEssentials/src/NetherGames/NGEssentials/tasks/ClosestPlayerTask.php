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

namespace NetherGames\NGEssentials\tasks;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function implode;
use const PHP_FLOAT_MAX;

class ClosestPlayerTask extends BaseTask
{

    public function __construct(private Player $player, NGEssentials $plugin)
    {
        parent::__construct($plugin);
    }

    public function onRun(): void
    {
        $plugin = $this->getPlugin();

        if (!$this->player->isConnected() || ($trackedName = $plugin->getPlayerData()->getString($this->player, PlayerData::TRACK)) === '') {
            $this->getHandler()->cancel();
            return;
        }

        $text = '';
        $playerManager = $plugin->getPlayerManager();
        $playerLocation = $this->player->getLocation();

        if (($trackPlayer = $plugin->getServer()->getPlayerExact($trackedName)) === null) {
            $text .= TextFormat::RED . $trackedName . '§r is not online';

            if (($trackPlayer = $this->getNearestPlayer($this->player)) === null) {
                $this->player->sendJukeboxPopup($text);
                return;
            }

            $text .= TextFormat::EOL;
        }

        if (($arena = $playerManager->isInArena($trackPlayer, true)) !== false) {
            if ($arena->isPrivateGame()) {
                $text .= TextFormat::GOLD . 'Private Game' . TextFormat::EOL;
            } elseif ($arena->isTouchOnly()) {
                $text .= TextFormat::GOLD . 'Touch Only Game' . TextFormat::EOL;
            }
        }

        /** @var NGPlayer $trackPlayer */
        $text .= TextFormat::YELLOW . 'Tracking: ' . TextFormat::GREEN . $trackPlayer->getName();
        $text .= TextFormat::YELLOW . ' | Device: ' . TextFormat::GREEN . $trackPlayer->getDeviceOS() . TextFormat::EOL;

        $text .= TextFormat::YELLOW . 'Distance: ' . TextFormat::GREEN . (int)($playerLocation->distance($trackPlayer->getLocation()));

        if (count($trackPlayer->getEffects()->all()) == 0) {
            $effects = "§cNone";
        } else {
            $effects = implode(", ", array_map(function ($effect) {
                $effectName = $effect->getType()->getName() instanceof Translatable ? $this->player->getServer()->getLanguage()->translate($effect->getType()->getName()) : $effect->getType()->getName();
                return TextFormat::GREEN . $effectName . " " . Utils::getRomanNumber($effect->getEffectLevel());
            }, $trackPlayer->getEffects()->all()));
        }

        $text .= TextFormat::YELLOW . ' | CPS: ' . TextFormat::GREEN . $trackPlayer->getCps() . TextFormat::YELLOW . " | Effects: " . $effects . TextFormat::EOL;

        $this->player->sendJukeboxPopup($text);
    }

    private function getNearestPlayer(Player $player): ?Player
    {
        $players = array_filter($this->player->getWorld()->getPlayers(), function (Player $player) {
            return !$player->isSpectator() && $player->getName() !== $this->player->getName();
        });

        $nearestPlayer = null;
        $location = $player->getLocation();
        $lastDistance = PHP_FLOAT_MAX;
        foreach ($players as $p) {
            $distance = $location->distanceSquared($p->getLocation());
            if ($distance < $lastDistance) {
                $lastDistance = $distance;
                $nearestPlayer = $p;
            }
        }

        return $nearestPlayer;
    }
}
