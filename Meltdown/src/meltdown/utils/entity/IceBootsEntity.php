<?php

namespace meltdown\utils\entity;

use meltdown\arena\MDArena;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class IceBootsEntity extends PowerupEntity{
    public function __construct(Location $location, MDArena $arena){
        parent::__construct($location, $arena);

        $this->setNameTag(TextFormat::BOLD . TextFormat::AQUA . "POWER-UP: " . TextFormat::RESET . TextFormat::DARK_BLUE . "Ice Boots");
    }

    public function onCollideWithPlayer(Player $player) : void{
        if($this->isFlaggedForDespawn()){
            return;
        }

        parent::onCollideWithPlayer($player);

        $player->sendMessage("§r§fYou have picked up the §bIce Boots §fpowerup!");
        $player->sendMessage("§r§fYour boots are cold, so the ice won’t melt under you!");
        $player->getEffects()->add(
            new EffectInstance(VanillaEffects::WATER_BREATHING(), 20 * 10, 1)
        );
    }

    protected function getAnimationId() : string{
        return "animation.ng.meltdown.powerup.ice_boots.float";
    }

    public static function getNetworkTypeId() : string{
        return "ng:meltdown_powerup_ice_boots";
    }
}