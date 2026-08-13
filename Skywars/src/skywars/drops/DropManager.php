<?php

declare(strict_types=1);

namespace skywars\drops;

use skywars\drops\list\AltF4Sign;
use skywars\drops\list\ArrowRain;
use skywars\drops\list\ChickenRain;
use skywars\drops\list\Effects;
use skywars\drops\list\Endermen;
use skywars\drops\list\FoodRain;
use skywars\drops\list\GoldenIngotRain;
use skywars\drops\list\Hole;
use skywars\drops\list\IgnitedTNT;
use skywars\drops\list\Items;
use skywars\drops\list\KeithVillager;
use skywars\drops\list\KnockBack;
use skywars\drops\list\LightningStrike;
use skywars\drops\list\ObsidianButton;
use skywars\drops\list\Sheep;
use skywars\drops\list\SkeletonBox;
use skywars\drops\list\UpsideDownGhast;
use skywars\drops\list\WebTrap;
use skywars\drops\list\WoodenWaterTrap;
use skywars\drops\list\Zombie;

class DropManager
{
    /** @var BaseDrop[] */
    private array $drops;

    public function __construct()
    {
        $this->registerDrops();
    }

    private function registerDrops(): void
    {
        $this->addDrop(new AltF4Sign());
        $this->addDrop(new ArrowRain());
        $this->addDrop(new ChickenRain());
        $this->addDrop(new Effects());
        $this->addDrop(new Endermen());
        $this->addDrop(new FoodRain());
        $this->addDrop(new GoldenIngotRain());
        $this->addDrop(new Hole());
        $this->addDrop(new IgnitedTNT());
        $this->addDrop(new Items());
        $this->addDrop(new KeithVillager());
        $this->addDrop(new KnockBack());
        $this->addDrop(new LightningStrike());
        $this->addDrop(new ObsidianButton());
        $this->addDrop(new Sheep());
        $this->addDrop(new SkeletonBox());
        $this->addDrop(new WebTrap());
        $this->addDrop(new UpsideDownGhast());
        $this->addDrop(new WoodenWaterTrap());
        $this->addDrop(new Zombie());
    }

    private function addDrop(BaseDrop $drop): void
    {
        $this->drops[] = $drop;
    }

    /**
     * @return BaseDrop
     */
    public function getPriorityRelativeRandomDrop(): BaseDrop
    {
        $drops = [];

        if (mt_rand(1, 100) <= 90) {
            $drops = $this->getDropsByPriority(BaseDrop::PRIORITY_ULTRA_HIGH);
        }
        if (empty($drops) && mt_rand(1, 100) <= 70) {
            $drops = $this->getDropsByPriority(BaseDrop::PRIORITY_HIGH);
        }
        if (empty($drops) && mt_rand(1, 100) <= 50) {
            $drops = $this->getDropsByPriority(BaseDrop::PRIORITY_MEDIUM);
        }
        if (empty($drops) && mt_rand(1, 100) <= 30) {
            $drops = $this->getDropsByPriority(BaseDrop::PRIORITY_LOW);
        }
        if (empty($drops) && mt_rand(1, 100) <= 10) {
            $drops = $this->getDropsByPriority(BaseDrop::PRIORITY_ULTRA_LOW);
        }

        return count($drops) > 0 ? clone $drops[array_rand($drops)] : $this->getRandomDrop();
    }

    /**
     * @param int $priority
     *
     * @return BaseDrop[]
     */
    public function getDropsByPriority(int $priority): array
    {
        return array_filter($this->drops, static function (BaseDrop $drop) use ($priority) {
            return $drop->getPriority() === $priority;
        });
    }

    /**
     * @return BaseDrop
     */
    public function getRandomDrop(): BaseDrop
    {
        return clone $this->drops[array_rand($this->drops)];
    }

    /**
     * @param array $priorityClasses will prioritize drops in this array with<br>
     * {@link BaseDrop} class names
     *
     * @return BaseDrop
     */
    public function getDropChanceRelativeRandomDrop(array $priorityClasses = []): BaseDrop
    {
        if (!empty($priorityClasses)) {
            $drops = array_filter($this->drops, static function (BaseDrop $drop) use ($priorityClasses) {
                return in_array($drop::class, $priorityClasses, true);
            });
            foreach ($drops as $drop) {
                if ($drop->willDrop()) {
                    return $drop;
                }
            }
        } else {
            foreach ($this->drops as $drop) {
                if ($drop->willDrop()) {
                    return $drop;
                }
            }
        }
        return $this->getRandomDrop();
    }
}