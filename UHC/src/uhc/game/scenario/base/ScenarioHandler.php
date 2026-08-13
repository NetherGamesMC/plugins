<?php
declare(strict_types=1);

namespace uhc\game\scenario\base;

use libminigames\events\MinigameStartEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerInteractEvent;

trait ScenarioHandler
{
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        foreach (ScenarioRegistry::getAll() as $scenario) {
            if (!$this->getArena()->isScenarioEnabled($scenario)) {
                continue;
            }
            $scenario->onBlockBreak($event);
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        foreach (ScenarioRegistry::getAll() as $scenario) {
            if (!$this->getArena()->isScenarioEnabled($scenario)) {
                continue;
            }
            $scenario->onEntityDamage($event);
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        foreach (ScenarioRegistry::getAll() as $scenario) {
            if (!$this->getArena()->isScenarioEnabled($scenario)) {
                continue;
            }
            $scenario->onPlayerInteract($event);
        }
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        foreach (ScenarioRegistry::getAll() as $scenario) {
            if (!$this->getArena()->isScenarioEnabled($scenario)) {
                continue;
            }
            $scenario->onCraftItem($event);
        }
    }

    public function onMinigameStart(MinigameStartEvent $event): void
    {
        foreach (ScenarioRegistry::getAll() as $scenario) {
            if (!$this->getArena()->isScenarioEnabled($scenario)) {
                continue;
            }
            $scenario->onMinigameStart($event);
        }
    }
}