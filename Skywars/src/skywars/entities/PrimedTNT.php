<?php

declare(strict_types=1);

namespace skywars\entities;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use function array_filter;
use function round;

class PrimedTNT extends \pocketmine\entity\object\PrimedTNT
{
    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->setCanSaveWithChunk(false);
    }

    public function explode(): void
    {
        $ev = new EntityPreExplodeEvent($this, 4);
        $ev->call();

        if (!$ev->isCancelled()) {
            //TODO: deal with underwater TNT (underwater TNT treats water as if it has a blast resistance of 0)
            $explosion = new Explosion(Position::fromObject($this->location->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()), $ev->getRadius(), $this);
            if ($ev->isBlockBreaking()) {
                $explosion->explodeA();

                $explosion->affectedBlocks = array_filter($explosion->affectedBlocks, static function (Block $block): bool {
                    return $block->getTypeId() !== VanillaBlocks::CHEST()->getTypeId();
                });
            }
            $explosion->explodeB();
        }
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($hasUpdate && $this->getFuse() % 2 === 0) {
            $this->setNameTag(TextFormat::RED . round($this->getFuse() / 20, 1));
        }

        return $hasUpdate;
    }
}