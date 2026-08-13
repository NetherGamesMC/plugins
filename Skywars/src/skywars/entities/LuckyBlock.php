<?php

declare(strict_types=1);

namespace skywars\entities;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use skywars\drops\list\Items;
use skywars\Skywars;
use skywars\SWArena;

class LuckyBlock extends Human
{
    private SWArena $arena;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt, SWArena $arena)
    {
        parent::__construct($location, $skin, $nbt);

        $this->setCanSaveWithChunk(false);
        $this->setHasGravity(false);

        $this->arena = $arena;
    }

    public function doOnFireTick(int $tickDiff = 1): bool
    {
        $this->extinguish();
        return false;
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {
                $this->drop($damager, $this->arena);
            }
        }
        $source->cancel();
    }

    final public function drop(Player $player, SWArena $arena): void
    {
        /** @var Skywars $plugin */
        $plugin = $arena->getPlugin();

        if (!$arena->isSpectator($player)) {
            $plugin->getDropManager()->getDropChanceRelativeRandomDrop([Items::class])->drop($player, $this, $arena);
            $this->flagForDespawn();
        }
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);

        if ($this->location->yaw >= 360) {
            $this->location->yaw = 0;
        }
        $this->location->yaw += 5;
        $this->broadcastMovement();

        return true;
    }
}