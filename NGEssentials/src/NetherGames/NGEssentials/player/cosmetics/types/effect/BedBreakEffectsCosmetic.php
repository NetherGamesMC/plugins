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

namespace NetherGames\NGEssentials\player\cosmetics\types\effect;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use pocketmine\player\Player;

class BedBreakEffectsCosmetic extends EffectCosmetic
{
    public function getName(): string
    {
        return 'Bed Break Effects';
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.kill_effect'; // todo: unique animation
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/fireworks', $callable);
    }
}