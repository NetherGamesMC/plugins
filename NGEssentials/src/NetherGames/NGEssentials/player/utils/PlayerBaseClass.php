<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\utils;


use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerManager;

//TODO: get rid of this, please stop
class PlayerBaseClass
{

    public function __construct(private PlayerManager $manager)
    {
    }

    public function getPlugin(): NGEssentials
    {
        return $this->getManager()->getPlugin();
    }

    public function getManager(): PlayerManager
    {
        return $this->manager;
    }
}