<?php

namespace uhc\game\scenario\base;

use libminigames\events\arena\ArenaStartEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerInteractEvent;

class ScenarioListener
{
    public function onBlockBreak(BlockBreakEvent $event): void
    {
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
    }

    public function onArenaStart(ArenaStartEvent $event): void
    {
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
    }
}