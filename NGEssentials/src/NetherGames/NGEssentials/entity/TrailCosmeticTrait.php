<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\entity;


use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use NetherGames\NGEssentials\player\cosmetics\types\particle\ArrowTrails;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

trait TrailCosmeticTrait
{
    private ArrowTrails $cosmetic;
    private ?CosmeticEntry $cosmeticEntry = null;

    public function onUpdate(int $currentTick): bool
    {
        $parent = parent::onUpdate($currentTick);

        if ($parent) {
            $this->runCosmetic();
        }

        return $parent;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->cosmetic = CosmeticHandler::ARROW_TRAILS();
    }

    public function runCosmetic(): void
    {
        if ($this->cosmeticEntry !== null && !$this->isOnGround()) {
            $this->cosmetic->run($this->cosmeticEntry, $this->getPosition());
        }
    }

    public function setOwningEntity(?Entity $owner): void
    {
        parent::setOwningEntity($owner);

        if ($owner instanceof Player) {
            $this->cosmeticEntry = $this->cosmetic->getSelectedEntry($owner);
        } else {
            $this->cosmeticEntry = null;
        }
    }
}