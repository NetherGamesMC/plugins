<?php

namespace NetherGames\NGEssentials\player\pets\events;

use NetherGames\NGEssentials\entity\pets\IPetEntity;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PetUnmountEvent extends PetEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(Entity&IPetEntity $pet, private readonly Player $rider)
    {
        parent::__construct($pet);
    }

    public function getRider(): Player
    {
        return $this->rider;
    }
}