<?php
/**
 *         _____
 *        / ____|
 *  __  _| (___   ___   ___ ___ ___ _ __
 *  \ \/ /\___ \ / _ \ / __/ __/ _ \ '__|
 *   >  < ____) | (_) | (_| (_|  __/ |
 *  /_/\_\_____/ \___/ \___\___\___|_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace soccer;

use libminigames\Arena;
use libminigames\ArenaListener;
use libminigames\TeamArena;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function preg_replace;
use function random_int;
use function strpos;

class SCArenaListener extends ArenaListener
{
    public function onPlayerChat(NGChatEvent $event): void
    {
        $player = $event->getPlayer();

        if ($this->getArena()->isSpectator($player)) {
            $event->setDisplayName(TextFormat::clean($player->getDisplayName()));
            $event->setRecipients($this->getArena()->getSpectators());
            $event->setPrefix('§7Dead Chat > ');
            $event->setStaffPrefix('§7Dead Chat Relay > ');
            $event->setSplitter(': ');
        } elseif ($this->getArena()->isSoloGame()) {
            $event->setDisplayName($player->getDisplayName());
        } else {
            $team = $this->getArena()->getTeam($player);
            $event->setDisplayName($team->getPlayerName($player));

            if ($this->getArena()->isRunning()) {
                if (str_starts_with(TextFormat::clean($event->getMessage()), '!')) {
                    $event->setMessage(preg_replace('/!/', '', $event->getMessage(), 1));
                } else {
                    $event->setRecipients($team->getAlivePlayers());
                    $event->setPrefix($team->getColor() . 'Team > ');
                    $event->setStaffPrefix('§fTeam Chat Relay > ');
                }
            }
        }
    }

    /**
     * @return TeamArena
     */
    public function getArena(): Arena
    {
        /** @var TeamArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $event->cancel();

        if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $player = $event->getEntity();

            if ($player instanceof Player) {
                /** @var SCTeam $team */
                $team = $this->getArena()->getTeam($player);
                $spawn = $team->getSpawn(random_int(0, 3));
                $player->teleport(new Location($spawn->getX(), $spawn->getY(), $spawn->getZ(), $this->getArena()->getWorld(), $spawn->getYaw(), 0.0));
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $event->cancel();
    }
}