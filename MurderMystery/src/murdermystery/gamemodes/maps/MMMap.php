<?php
/**
 *                                _                                   _
 *       /'\_/`\                 ( )             /'\_/`\             ( )_
 *       |     | _   _  _ __    _| |   __   _ __ |     | _   _   ___ | ,_)   __   _ __  _   _
 * (`\/')| (_) |( ) ( )( '__) /'_` | /'__`\( '__)| (_) |( ) ( )/',__)| |   /'__`\( '__)( ) ( )
 *  >  < | | | || (_) || |   ( (_| |(  ___/| |   | | | || (_) |\__, \| |_ (  ___/| |   | (_) |
 * (_/\_)(_) (_)`\___/'(_)   `\__,_)`\____)(_)   (_) (_)`\__, |(____/`\__)`\____)(_)   `\__, |
 *                                                      ( )_| |                        ( )_| |
 *                                                      `\___/'                        `\___/'
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace murdermystery\gamemodes\maps;

use murdermystery\gamemodes\MMArena;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\world\World;

abstract class MMMap
{
    /** @var MMArena */
    protected MMArena $arena;

    public function __construct(MMArena $arena)
    {
        $this->arena = $arena;
        $this->loadElements();
    }

    /**
     * It's used for load features in the map (like FloatingTextParticle, etc...)
     */
    abstract public function loadElements(): void;

    public static function newInstance(MMArena $arena): MMMap
    {
        return new EmptyMap($arena);
    }

    final public function getWorld(): World
    {
        return $this->arena->getWorld();
    }

    public function getArena(): MMArena
    {
        return $this->arena;
    }

    abstract public function onPlayerInteract(PlayerInteractEvent $event): bool;

    abstract public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void;

    abstract public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void;

    abstract public function onBlockPlace(BlockPlaceEvent $event): void;
}