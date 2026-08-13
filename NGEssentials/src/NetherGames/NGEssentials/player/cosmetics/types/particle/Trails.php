<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\cosmetics\types\particle;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use NetherGames\NGEssentials\player\cosmetics\traits\ParticleCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Trails extends Cosmetic
{
    use ParticleCosmeticTrait;

    public function onTick(Player $player, Position $pos): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            $this->getOptimizer()->addParticle($this->getParticle($entry->getDataEntry()), $pos, $pos->getWorld());
        }
    }

    public function getName(): string
    {
        return 'Trails';
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.particle_trail';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/dragons_breath', $callable);
    }
}