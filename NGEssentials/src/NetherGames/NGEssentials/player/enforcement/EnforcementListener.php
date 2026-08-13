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

namespace NetherGames\NGEssentials\player\enforcement;

use libminigames\utils\Items;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class EnforcementListener implements Listener
{
    /** @var array<int, true> */
    private array $catchDummyHeldChange = [];

    public function __construct(private Enforcement $enforcer)
    {
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority LOW
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $ess = $this->enforcer->getPlugin();

        if ($event->isPreLoaded()) {
            if (($spectatedName = $ess->getPlayerData()->getString($player, PlayerData::TRACK)) !== '') {
                if (($spectated = $ess->getServer()->getPlayerExact($spectatedName)) === null) {
                    $player->sendMessage('§6' . $spectatedName . ' §cis no longer on this server.');
                    $ess->getPlayerManager()->transferPlayer($player);
                } else if (!$this->enforcer->canUseTracking($player)) {
                    $ess->getPlayerData()->unsetValue($player, PlayerData::TRACK);
                } else {
                    $ess->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($ess, $player, $spectated): void {
                        if ($player->isConnected()) {
                            if ($spectated->isConnected()) {
                                $this->enforcer->setupTracking($player, $spectated);
                            } else {
                                $player->sendMessage('§6' . $spectated->getName() . ' §cis no longer on this server.');
                                $ess->getPlayerManager()->transferPlayer($player);
                            }
                        }
                    }), 20);
                }
            }
        } elseif (Permissions::isStaff($player) && !$ess->getPlayerData()->getBool($player, PlayerData::STAFF_NOTIFICATIONS)) {
            $player->sendMessage(TextFormat::RED . "Your staff notifications are disabled! :( big sad");
        }
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$this->enforcer->getPlugin()->getPlayerData()->getBool($player, PlayerData::SETUP)) {
            $event->cancel();
        }
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        /** @var NGPlayer $player */
        if ($item->equals(LobbyItems::getStaffPortalItem())) {
            if (Permissions::isStaff($player)) {
                $this->enforcer->sendStaffPortal($player);
            } else {
                Translator::sendMessage($player, "menu.blocked", Translator::TYPE_ERROR);
            }
        } else {
            $plugin = $this->enforcer->getPlugin();
            $playerData = $plugin->getPlayerData();
            $tracking = $playerData->getBool($player, PlayerData::TRACK);

            $replay = null;
            if (($replayManager = \libReplay\session\replay\ReplayManager::getInstance()) !== null) {
                $replay = $replayManager->getReplay($player->getWorld());
            }

            if ($tracking || $replay !== null) {
                if ($tracking && $item->equals(LobbyItems::getSpectatorCompass(), false)) {
                    Forms::sendTrackMenu($player);
                } elseif ($item->equals(LobbyItems::getNoClipToggleItem(), false)) {
                    if ($player->hasBlockCollision()) {
                        $player->setHasBlockCollision(false);
                        $player->sendMessage('§aEnabled no clip mode. You will be able to go through blocks.');
                    } else {
                        $player->setHasBlockCollision(true);
                        $player->sendMessage('§cDisabled no clip mode. You will no longer be able to go through blocks.');
                    }
                } elseif ($item->equals(LobbyItems::getSpectatorBed(), false)) {
                    if ($tracking) {
                        $this->enforcer->setTracking($player, false);
                    } else {
                        $player->sendMessage('§aDisabled replay mode and returned to the lobby.');

                        $player->setHasBlockCollision(true);
                        $player->getEffects()->remove(VanillaEffects::NIGHT_VISION());

                        $replayManager->stopReplay($player, $player->getWorld());
                        $plugin->getPlayerManager()->transferPlayer($player);
                    }
                } elseif ($replay !== null) {
                    $pauseItemSelected = $item->equals(LobbyItems::getPauseReplayTorch());
                    $resumeItemSelected = $item->equals(LobbyItems::getResumeReplayTorch());

                    if ($pauseItemSelected || $resumeItemSelected) {
                        $player->sendMessage($pauseItemSelected ?
                            TextFormat::RED . 'The replay has been paused.' :
                            TextFormat::GREEN . 'The replay has been resumed.'
                        );

                        if ($pauseItemSelected) {
                            $replay->pause();
                        } else {
                            $replay->resume();
                        }

                        foreach ($player->getWorld()->getPlayers() as $p) {
                            if ($p !== $player) {
                                $p->sendMessage($pauseItemSelected ?
                                    TextFormat::RED . $player->getName() . ' paused the replay.' :
                                    TextFormat::GREEN . $player->getName() . ' resumed the replay.'
                                );
                            }

                            if ($p->getInventory()->getHeldItemIndex() === ($itemIndex = 4) && !Utils::hasClassicUI($p)) {
                                $this->catchDummyHeldChange[$p->getId()] = true;
                            }

                            $p->getInventory()->setItem($itemIndex, $pauseItemSelected ?
                                LobbyItems::getResumeReplayTorch() :
                                LobbyItems::getPauseReplayTorch()
                            );
                        }
                    } elseif ($item->equals(LobbyItems::getSpeedReplayFeather(), false, false)) {
                        $speed = $item->getCount();

                        $newItem = match ($speed) {
                            1 => LobbyItems::getSpeedReplayFeather(2),
                            2 => LobbyItems::getSpeedReplayFeather(3),
                            default => LobbyItems::getSpeedReplayFeather(),
                        };

                        $newSpeed = $newItem->getCount();
                        $replay->setSpeed($newSpeed);

                        $player->sendMessage(TextFormat::GREEN . 'The replay speed has been set to ' . $newSpeed . 'x.');

                        foreach ($player->getWorld()->getPlayers() as $p) {
                            if ($p !== $player) {
                                $p->sendMessage(TextFormat::GREEN . $player->getName() . ' set the replay speed to ' . $newSpeed . 'x.');
                            }

                            if ($p->getInventory()->getHeldItemIndex() === ($itemIndex = 7) && !Utils::hasClassicUI($p)) {
                                $this->catchDummyHeldChange[$p->getId()] = true;
                            }

                            $p->getInventory()->setItem($itemIndex, $newItem);
                        }
                    } elseif ($item->equals(Items::getSpectatorCompass())) {
                        $replay->getPlayerSelector($player);
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param PlayerItemUseEvent $event
     *
     * @priority LOW
     * @handleCancelled
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$event->isCancelled() || $player->isSpectator()) {
            if ($this->onItemInteract($player, $event->getItem())) {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     *
     * @priority LOW
     */
    public function onPlayerHeldItem(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Utils::hasClassicUI($player)) {
            if (isset($this->catchDummyHeldChange[$player->getId()])) {
                unset($this->catchDummyHeldChange[$player->getId()]);
            } else {
                $this->onItemInteract($player, $event->getItem());
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority NORMAL
     * @handleCancelled
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player && $event instanceof EntityDamageByEntityEvent) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();

            if ($damager instanceof Player && $entity instanceof Player && !$event instanceof EntityDamageByChildEntityEvent && $damager->getInventory()->getItemInHand()->equals(LobbyItems::getStaffPortalItem(), false)) {
                $this->enforcer->sendPlayerEditor($damager, $entity->getServer()->getOfflinePlayer($entity->getName()));
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        if (($replayManager = \libReplay\session\replay\ReplayManager::getInstance()) !== null) {
            $replayManager->stopReplay($player, $player->getWorld());

            unset($this->catchDummyHeldChange[$player->getId()]);
        }
    }
}
