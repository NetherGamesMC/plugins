<?php

namespace meltdown\utils\entity;

use meltdown\arena\MDArena;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SlipperyBootsEntity extends PowerupEntity{
    public function __construct(Location $location, MDArena $arena){
        parent::__construct($location, $arena);

        $this->setNameTag(TextFormat::BOLD . TextFormat::AQUA . "POWER-UP: " . TextFormat::RESET . TextFormat::GRAY . "Slippery Boots");
    }

    public function onCollideWithPlayer(Player $player) : void{
        if($this->isFlaggedForDespawn()){
            return;
        }

        parent::onCollideWithPlayer($player);

        $player->sendMessage("§r§fYou have picked up the §bSlippery Boots §fpowerup!");
        $player->getEffects()->add(
            new EffectInstance(VanillaEffects::SPEED(), 20 * 10, 1)
        );
    }

    public static function getNetworkTypeId() : string{
        return "ng:meltdown_powerup_slippery_boots";
    }

    protected function getAnimationId() : string{
        return "animation.ng.meltdown.powerup.slippery_boots.float";
    }
}