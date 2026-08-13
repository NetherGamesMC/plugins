<?php

namespace meltdown\utils\entity;

use meltdown\arena\MDArena;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use meltdown\utils\Items;
use pocketmine\utils\TextFormat;

class SnowBombEntity extends PowerupEntity{
    public function __construct(Location $location, MDArena $arena){
        parent::__construct($location, $arena);

        $this->setNameTag(TextFormat::BOLD . TextFormat::AQUA . "POWER-UP: " . TextFormat::RESET . TextFormat::YELLOW . "Snowbomb");
    }

    public function onCollideWithPlayer(Player $player) : void{
        if($this->isFlaggedForDespawn()){
            return;
        }

        parent::onCollideWithPlayer($player);

        $player->sendMessage("§r§fYou have picked up a §bSnowbomb!");
        $player->getInventory()->addItem(Items::getSnowBomb());
    }

    protected function getAnimationId() : string{
        return "animation.ng.meltdown.powerup.snowbomb.float";
    }

    public static function getNetworkTypeId() : string{
        return "ng:meltdown_powerup_snowbomb";
    }
}