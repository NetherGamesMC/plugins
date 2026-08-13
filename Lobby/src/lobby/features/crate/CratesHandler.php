<?php

declare(strict_types=1);

namespace lobby\features\crate;

use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use pocketmine\math\Vector3;

class CratesHandler
{
    /** @var Crate[] */
    private array $crates = [];

    public function __construct(private CosmeticHandler $handler)
    {
        $position = new Vector3(13, 41, 31);
        $this->crates[(string)$position] = new Crate($this, $position);
    }

    public function getPlugin(): NGEssentials
    {
        return $this->handler->getPlugin();
    }

    public function getCrate(Vector3 $vector3): ?Crate
    {
        return $this->getCrates()[(string)$vector3->asVector3()] ?? null;
    }

    /**
     * @return Crate[]
     */
    public function getCrates(): array
    {
        return $this->crates;
    }

    public function getCosmeticHandler(): CosmeticHandler
    {
        return $this->handler;
    }
}