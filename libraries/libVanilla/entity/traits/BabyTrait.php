<?php

declare(strict_types=1);


namespace libVanilla\entity\traits;


use libVanilla\entity\animation\BreedAnimation;
use libVanilla\entity\Breedable;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use function in_array;
use function mt_rand;

trait BabyTrait
{
    /** @var bool */
    protected bool $baby = false;
    /** @var int */
    private int $age = 0;
    /** @var int */
    private int $breedTimeOut = 0;
    /** @var int */
    private int $inLove = 0;

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
        $this->setBaby($age < 0);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setByte('IsBaby', $this->isBaby() ? 1 : 0);
        $nbt->setInt('Age', $this->age);

        return $nbt;
    }

    public function isBaby(): bool
    {
        return $this->baby;
    }

    public function setBaby(bool $baby): void
    {
        $this->baby = $baby;
        $this->setScale($baby ? 0.5 : 1.0);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $parent = parent::entityBaseTick($tickDiff);

        if ($this->baby) {
            $this->setAge(++$this->age);
        } elseif ($this instanceof Breedable) {
            if ($this->breedTimeOut > 0) {
                $this->breedTimeOut--;
            } elseif ($this->inLove > 0) {
                $this->inLove--;

                if ($this->inLove % 20 === 0) {
                    foreach ($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(2, 2, 2), $this) as $entity) {
                        if ($entity instanceof self && $entity->isInLove()) {
                            $entity->broadcastAnimation(new BreedAnimation($entity));

                            $baby = $entity->createEntity($entity->getLocation());
                            $baby->setAge(-20 * 60 * 20);
                            $baby->spawnToAll();

                            $entity->getWorld()->dropExperience($entity->getPosition(), mt_rand(0, 7));

                            $this->setInLove(false);
                            $entity->setInLove(false);
                        }
                    }
                }
            }
        }

        return $parent;
    }

    public function isInLove(): bool
    {
        return $this->inLove > 0;
    }

    public function setInLove(bool $inLove = true): void
    {
        if (!$inLove) {
            $this->breedTimeOut = 6000;
        }

        $this->inLove = $inLove ? 5000 : 0;
        // todo: sync
    }

    public function isInteresting(Entity $entity): bool
    {
        if ($this instanceof Breedable && $entity instanceof Player && in_array($entity->getInventory()->getItemInHand()->getTypeId(), $this->getBreedingItems(), true)) {
            return true;
        }

        return parent::isInteresting($entity);
    }

    /**
     * @return int[]
     */
    public function getBreedingItems(): array
    {
        return [];
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        $item = $player->getInventory()->getItemInHand();

        if (in_array($item->getTypeId(), $this->getBreedingItems(), true)) {
            $item->pop();
            $player->getInventory()->setItemInHand($item);

            if ($this->breedTimeOut === 0) {
                $this->setInLove();
            }
            return true;
        }

        return parent::onInteract($player, $clickPos);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setBaby((bool)$nbt->getByte('IsBaby', 0));
        $this->age = $nbt->getInt('Age', 0);
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::BABY, $this->baby);
        $properties->setGenericFlag(EntityMetadataFlags::INLOVE, $this->inLove > 0);
    }
}