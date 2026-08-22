<?php
declare(strict_types=1);

namespace uhc\game\scenario;

use libminigames\events\arena\ArenaStartEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use uhc\game\scenario\base\Scenario;

class Superheroes extends Scenario
{
    public function onArenaStart(ArenaStartEvent $event): void
    {
        foreach ($event->getArena()->getAlivePlayers() as $player) {
            $this->applySuperpower($player);
        }
    }

    private function applySuperpower(Player $player): void
    {
        $effects = $player->getEffects();
        switch (mt_rand(0, 4)) {
            case 0:
                $effects->add(new EffectInstance(VanillaEffects::SPEED(), 2147483647, 0, false));
                $player->sendMessage("§eYour superpower is §aspeed§e!");
                break;
            case 1:
                $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 2147483647, 0, false));
                $player->sendMessage("§eYour superpower is §astrength§e!");
                break;
            case 2:
                $effects->add(new EffectInstance(VanillaEffects::RESISTANCE(), 2147483647, 1, false));
                $player->sendMessage("§eYour superpower is §aresistance§e!");
                break;
            case 3:
                $effects->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 2147483647, 0, false));
                $player->sendMessage("§eYour superpower is §ainvisibility§e!");
                break;
            case 4:
                $effects->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 2147483647, 3, false));
                $player->sendMessage("§eYour superpower is §ajump boost§e!");
                break;
        }
    }
}