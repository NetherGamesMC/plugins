<?php
declare(strict_types=1);

namespace libVanilla\item;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\ItemIdentifier;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class SpawnEgg extends \pocketmine\item\SpawnEgg
{
    /**
     * @param ItemIdentifier $identifier
     * @param string $name
     * @param class-string<Entity> $entityClass
     */
    public function __construct(ItemIdentifier $identifier, string $name, private string $entityClass)
    {
        parent::__construct($identifier, $name);
    }

    public function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Entity
    {
        return new $this->entityClass(Location::fromObject($pos, $world, $yaw, $pitch));
    }
}
