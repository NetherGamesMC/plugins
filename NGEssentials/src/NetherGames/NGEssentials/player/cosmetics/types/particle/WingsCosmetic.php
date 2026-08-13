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
use pocketmine\math\Facing;
use pocketmine\player\Player;

class WingsCosmetic extends Cosmetic
{
    use ParticleCosmeticTrait;

    public function onTick(Player $player): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            $particle = $this->getParticle($entry->getDataEntry());
            $playerPosition = $player->getPosition()->add(0, 1.2, 0);

            $positions = [];
            switch ($player->getHorizontalFacing()) {
                case Facing::EAST:
                    $positions[] = $playerPosition->add(-0.5, 1.4, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 1.4, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 1.2, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 1.2, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 1.2, 1);
                    $positions[] = $playerPosition->add(-0.5, 1.2, -1);
                    $positions[] = $playerPosition->add(-0.5, 1, 0.6);
                    $positions[] = $playerPosition->add(-0.5, 1, -0.6);
                    $positions[] = $playerPosition->add(-0.5, 1, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 1, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 1, 1);
                    $positions[] = $playerPosition->add(-0.5, 1, -1);
                    $positions[] = $playerPosition->add(-0.5, 1, 1.2);
                    $positions[] = $playerPosition->add(-0.5, 1, -1.2);
                    $positions[] = $playerPosition->add(-0.5, 0.8, 0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.8, -0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.8, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.8, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.8, 1);
                    $positions[] = $playerPosition->add(-0.5, 0.8, -1);
                    $positions[] = $playerPosition->add(-0.5, 0.8, 1.2);
                    $positions[] = $playerPosition->add(-0.5, 0.8, -1.2);
                    $positions[] = $playerPosition->add(-0.5, 0.6, 0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.6, -0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.6, 0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.6, -0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.6, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.6, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.6, 1);
                    $positions[] = $playerPosition->add(-0.5, 0.6, -1);
                    $positions[] = $playerPosition->add(-0.5, 0.4, 0.2);
                    $positions[] = $playerPosition->add(-0.5, 0.4, -0.2);
                    $positions[] = $playerPosition->add(-0.5, 0.4, 0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.4, -0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.4, 0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.4, -0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.4, 0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.4, -0.8);
                    $positions[] = $playerPosition->add(-0.5, 0.2, 0);
                    $positions[] = $playerPosition->add(-0.5, 0.2, -0);
                    $positions[] = $playerPosition->add(-0.5, 0.2, 0.2);
                    $positions[] = $playerPosition->add(-0.5, 0.2, -0.2);
                    $positions[] = $playerPosition->add(-0.5, 0.2, 0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.2, -0.4);
                    $positions[] = $playerPosition->add(-0.5, 0.2, 0.6);
                    $positions[] = $playerPosition->add(-0.5, 0.2, -0.6);
                    $positions[] = $playerPosition->add(-0.5, 0, 0);
                    $positions[] = $playerPosition->add(-0.5, 0, -0);
                    $positions[] = $playerPosition->add(-0.5, 0, 0.2);
                    $positions[] = $playerPosition->add(-0.5, 0, -0.2);
                    $positions[] = $playerPosition->add(-0.5, 0, 0.4);
                    $positions[] = $playerPosition->add(-0.5, 0, -0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.2, 0);
                    $positions[] = $playerPosition->add(-0.5, -0.2, -0);
                    $positions[] = $playerPosition->add(-0.5, -0.2, 0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.2, -0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.4, 0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.4, -0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.4, 0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.4, -0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.6, 0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.6, -0.2);
                    $positions[] = $playerPosition->add(-0.5, -0.6, 0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.6, -0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.6, 0.6);
                    $positions[] = $playerPosition->add(-0.5, -0.6, -0.6);
                    $positions[] = $playerPosition->add(-0.5, -0.8, 0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.8, -0.4);
                    $positions[] = $playerPosition->add(-0.5, -0.8, 0.6);
                    $positions[] = $playerPosition->add(-0.5, -0.8, -0.6);
                    $positions[] = $playerPosition->add(-0.5, -1, 0.6);
                    $positions[] = $playerPosition->add(-0.5, -1, -0.6);
                    break;
                case Facing::SOUTH:
                    $positions[] = $playerPosition->add(0.8, 1.4, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 1.4, -0.5);
                    $positions[] = $playerPosition->add(0.8, 1.2, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 1.2, -0.5);
                    $positions[] = $playerPosition->add(1, 1.2, -0.5);
                    $positions[] = $playerPosition->add(-1, 1.2, -0.5);
                    $positions[] = $playerPosition->add(0.6, 1, -0.5);
                    $positions[] = $playerPosition->add(-0.6, 1, -0.5);
                    $positions[] = $playerPosition->add(0.8, 1, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 1, -0.5);
                    $positions[] = $playerPosition->add(1, 1, -0.5);
                    $positions[] = $playerPosition->add(-1, 1, -0.5);
                    $positions[] = $playerPosition->add(1.2, 1, -0.5);
                    $positions[] = $playerPosition->add(-1.2, 1, -0.5);
                    $positions[] = $playerPosition->add(0.6, 0.8, -0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.8, -0.5);
                    $positions[] = $playerPosition->add(0.8, 0.8, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.8, -0.5);
                    $positions[] = $playerPosition->add(1, 0.8, -0.5);
                    $positions[] = $playerPosition->add(-1, 0.8, -0.5);
                    $positions[] = $playerPosition->add(1.2, 0.8, -0.5);
                    $positions[] = $playerPosition->add(-1.2, 0.8, -0.5);
                    $positions[] = $playerPosition->add(0.4, 0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.6, -0.5);
                    $positions[] = $playerPosition->add(0.6, 0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.6, -0.5);
                    $positions[] = $playerPosition->add(0.8, 0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.6, -0.5);
                    $positions[] = $playerPosition->add(1, 0.6, -0.5);
                    $positions[] = $playerPosition->add(-1, 0.6, -0.5);
                    $positions[] = $playerPosition->add(0.2, 0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.2, 0.4, -0.5);
                    $positions[] = $playerPosition->add(0.4, 0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.4, -0.5);
                    $positions[] = $playerPosition->add(0.6, 0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.4, -0.5);
                    $positions[] = $playerPosition->add(0.8, 0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.4, -0.5);
                    $positions[] = $playerPosition->add(0, 0.2, -0.5);
                    $positions[] = $playerPosition->add(-0, 0.2, -0.5);
                    $positions[] = $playerPosition->add(0.2, 0.2, -0.5);
                    $positions[] = $playerPosition->add(-0.2, 0.2, -0.5);
                    $positions[] = $playerPosition->add(0.4, 0.2, -0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.2, -0.5);
                    $positions[] = $playerPosition->add(0.6, 0.2, -0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.2, -0.5);
                    $positions[] = $playerPosition->add(0, 0, -0.5);
                    $positions[] = $playerPosition->add(-0, 0, -0.5);
                    $positions[] = $playerPosition->add(0.2, 0, -0.5);
                    $positions[] = $playerPosition->add(-0.2, 0, -0.5);
                    $positions[] = $playerPosition->add(0.4, 0, -0.5);
                    $positions[] = $playerPosition->add(-0.4, 0, -0.5);
                    $positions[] = $playerPosition->add(0, -0.2, -0.5);
                    $positions[] = $playerPosition->add(-0, -0.2, -0.5);
                    $positions[] = $playerPosition->add(0.2, -0.2, -0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.2, -0.5);
                    $positions[] = $playerPosition->add(0.2, -0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.4, -0.5);
                    $positions[] = $playerPosition->add(0.4, -0.4, -0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.4, -0.5);
                    $positions[] = $playerPosition->add(0.2, -0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.6, -0.5);
                    $positions[] = $playerPosition->add(0.4, -0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.6, -0.5);
                    $positions[] = $playerPosition->add(0.6, -0.6, -0.5);
                    $positions[] = $playerPosition->add(-0.6, -0.6, -0.5);
                    $positions[] = $playerPosition->add(0.4, -0.8, -0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.8, -0.5);
                    $positions[] = $playerPosition->add(0.6, -0.8, -0.5);
                    $positions[] = $playerPosition->add(-0.6, -0.8, -0.5);
                    $positions[] = $playerPosition->add(0.6, -1, -0.5);
                    $positions[] = $playerPosition->add(-0.6, -1, -0.5);
                    break;
                case Facing::WEST:
                    $positions[] = $playerPosition->add(0.5, 1.4, 0.8);
                    $positions[] = $playerPosition->add(0.5, 1.4, -0.8);
                    $positions[] = $playerPosition->add(0.5, 1.2, 0.8);
                    $positions[] = $playerPosition->add(0.5, 1.2, -0.8);
                    $positions[] = $playerPosition->add(0.5, 1.2, 1);
                    $positions[] = $playerPosition->add(0.5, 1.2, -1);
                    $positions[] = $playerPosition->add(0.5, 1, 0.6);
                    $positions[] = $playerPosition->add(0.5, 1, -0.6);
                    $positions[] = $playerPosition->add(0.5, 1, 0.8);
                    $positions[] = $playerPosition->add(0.5, 1, -0.8);
                    $positions[] = $playerPosition->add(0.5, 1, 1);
                    $positions[] = $playerPosition->add(0.5, 1, -1);
                    $positions[] = $playerPosition->add(0.5, 1, 1.2);
                    $positions[] = $playerPosition->add(0.5, 1, -1.2);
                    $positions[] = $playerPosition->add(0.5, 0.8, 0.6);
                    $positions[] = $playerPosition->add(0.5, 0.8, -0.6);
                    $positions[] = $playerPosition->add(0.5, 0.8, 0.8);
                    $positions[] = $playerPosition->add(0.5, 0.8, -0.8);
                    $positions[] = $playerPosition->add(0.5, 0.8, 1);
                    $positions[] = $playerPosition->add(0.5, 0.8, -1);
                    $positions[] = $playerPosition->add(0.5, 0.8, 1.2);
                    $positions[] = $playerPosition->add(0.5, 0.8, -1.2);
                    $positions[] = $playerPosition->add(0.5, 0.6, 0.4);
                    $positions[] = $playerPosition->add(0.5, 0.6, -0.4);
                    $positions[] = $playerPosition->add(0.5, 0.6, 0.6);
                    $positions[] = $playerPosition->add(0.5, 0.6, -0.6);
                    $positions[] = $playerPosition->add(0.5, 0.6, 0.8);
                    $positions[] = $playerPosition->add(0.5, 0.6, -0.8);
                    $positions[] = $playerPosition->add(0.5, 0.6, 1);
                    $positions[] = $playerPosition->add(0.5, 0.6, -1);
                    $positions[] = $playerPosition->add(0.5, 0.4, 0.2);
                    $positions[] = $playerPosition->add(0.5, 0.4, -0.2);
                    $positions[] = $playerPosition->add(0.5, 0.4, 0.4);
                    $positions[] = $playerPosition->add(0.5, 0.4, -0.4);
                    $positions[] = $playerPosition->add(0.5, 0.4, 0.6);
                    $positions[] = $playerPosition->add(0.5, 0.4, -0.6);
                    $positions[] = $playerPosition->add(0.5, 0.4, 0.8);
                    $positions[] = $playerPosition->add(0.5, 0.4, -0.8);
                    $positions[] = $playerPosition->add(0.5, 0.2, 0);
                    $positions[] = $playerPosition->add(0.5, 0.2, -0);
                    $positions[] = $playerPosition->add(0.5, 0.2, 0.2);
                    $positions[] = $playerPosition->add(0.5, 0.2, -0.2);
                    $positions[] = $playerPosition->add(0.5, 0.2, 0.4);
                    $positions[] = $playerPosition->add(0.5, 0.2, -0.4);
                    $positions[] = $playerPosition->add(0.5, 0.2, 0.6);
                    $positions[] = $playerPosition->add(0.5, 0.2, -0.6);
                    $positions[] = $playerPosition->add(0.5, 0, 0);
                    $positions[] = $playerPosition->add(0.5, 0, -0);
                    $positions[] = $playerPosition->add(0.5, 0, 0.2);
                    $positions[] = $playerPosition->add(0.5, 0, -0.2);
                    $positions[] = $playerPosition->add(0.5, 0, 0.4);
                    $positions[] = $playerPosition->add(0.5, 0, -0.4);
                    $positions[] = $playerPosition->add(0.5, -0.2, 0);
                    $positions[] = $playerPosition->add(0.5, -0.2, -0);
                    $positions[] = $playerPosition->add(0.5, -0.2, 0.2);
                    $positions[] = $playerPosition->add(0.5, -0.2, -0.2);
                    $positions[] = $playerPosition->add(0.5, -0.4, 0.2);
                    $positions[] = $playerPosition->add(0.5, -0.4, -0.2);
                    $positions[] = $playerPosition->add(0.5, -0.4, 0.4);
                    $positions[] = $playerPosition->add(0.5, -0.4, -0.4);
                    $positions[] = $playerPosition->add(0.5, -0.6, 0.2);
                    $positions[] = $playerPosition->add(0.5, -0.6, -0.2);
                    $positions[] = $playerPosition->add(0.5, -0.6, 0.4);
                    $positions[] = $playerPosition->add(0.5, -0.6, -0.4);
                    $positions[] = $playerPosition->add(0.5, -0.6, 0.6);
                    $positions[] = $playerPosition->add(0.5, -0.6, -0.6);
                    $positions[] = $playerPosition->add(0.5, -0.8, 0.4);
                    $positions[] = $playerPosition->add(0.5, -0.8, -0.4);
                    $positions[] = $playerPosition->add(0.5, -0.8, 0.6);
                    $positions[] = $playerPosition->add(0.5, -0.8, -0.6);
                    $positions[] = $playerPosition->add(0.5, -1, 0.6);
                    $positions[] = $playerPosition->add(0.5, -1, -0.6);
                    break;
                case Facing::NORTH:
                    $positions[] = $playerPosition->add(0.8, 1.4, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 1.4, 0.5);
                    $positions[] = $playerPosition->add(0.8, 1.2, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 1.2, 0.5);
                    $positions[] = $playerPosition->add(1, 1.2, 0.5);
                    $positions[] = $playerPosition->add(-1, 1.2, 0.5);
                    $positions[] = $playerPosition->add(0.6, 1, 0.5);
                    $positions[] = $playerPosition->add(-0.6, 1, 0.5);
                    $positions[] = $playerPosition->add(0.8, 1, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 1, 0.5);
                    $positions[] = $playerPosition->add(1, 1, 0.5);
                    $positions[] = $playerPosition->add(-1, 1, 0.5);
                    $positions[] = $playerPosition->add(1.2, 1, 0.5);
                    $positions[] = $playerPosition->add(-1.2, 1, 0.5);
                    $positions[] = $playerPosition->add(0.6, 0.8, 0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.8, 0.5);
                    $positions[] = $playerPosition->add(0.8, 0.8, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.8, 0.5);
                    $positions[] = $playerPosition->add(1, 0.8, 0.5);
                    $positions[] = $playerPosition->add(-1, 0.8, 0.5);
                    $positions[] = $playerPosition->add(1.2, 0.8, 0.5);
                    $positions[] = $playerPosition->add(-1.2, 0.8, 0.5);
                    $positions[] = $playerPosition->add(0.4, 0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.6, 0.5);
                    $positions[] = $playerPosition->add(0.6, 0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.6, 0.5);
                    $positions[] = $playerPosition->add(0.8, 0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.6, 0.5);
                    $positions[] = $playerPosition->add(1, 0.6, 0.5);
                    $positions[] = $playerPosition->add(-1, 0.6, 0.5);
                    $positions[] = $playerPosition->add(0.2, 0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.2, 0.4, 0.5);
                    $positions[] = $playerPosition->add(0.4, 0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.4, 0.5);
                    $positions[] = $playerPosition->add(0.6, 0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.4, 0.5);
                    $positions[] = $playerPosition->add(0.8, 0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.8, 0.4, 0.5);
                    $positions[] = $playerPosition->add(0, 0.2, 0.5);
                    $positions[] = $playerPosition->add(-0, 0.2, 0.5);
                    $positions[] = $playerPosition->add(0.2, 0.2, 0.5);
                    $positions[] = $playerPosition->add(-0.2, 0.2, 0.5);
                    $positions[] = $playerPosition->add(0.4, 0.2, 0.5);
                    $positions[] = $playerPosition->add(-0.4, 0.2, 0.5);
                    $positions[] = $playerPosition->add(0.6, 0.2, 0.5);
                    $positions[] = $playerPosition->add(-0.6, 0.2, 0.5);
                    $positions[] = $playerPosition->add(0, 0, 0.5);
                    $positions[] = $playerPosition->add(-0, 0, 0.5);
                    $positions[] = $playerPosition->add(0.2, 0, 0.5);
                    $positions[] = $playerPosition->add(-0.2, 0, 0.5);
                    $positions[] = $playerPosition->add(0.4, 0, 0.5);
                    $positions[] = $playerPosition->add(-0.4, 0, 0.5);
                    $positions[] = $playerPosition->add(0, -0.2, 0.5);
                    $positions[] = $playerPosition->add(-0, -0.2, 0.5);
                    $positions[] = $playerPosition->add(0.2, -0.2, 0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.2, 0.5);
                    $positions[] = $playerPosition->add(0.2, -0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.4, 0.5);
                    $positions[] = $playerPosition->add(0.4, -0.4, 0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.4, 0.5);
                    $positions[] = $playerPosition->add(0.2, -0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.2, -0.6, 0.5);
                    $positions[] = $playerPosition->add(0.4, -0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.6, 0.5);
                    $positions[] = $playerPosition->add(0.6, -0.6, 0.5);
                    $positions[] = $playerPosition->add(-0.6, -0.6, 0.5);
                    $positions[] = $playerPosition->add(0.4, -0.8, 0.5);
                    $positions[] = $playerPosition->add(-0.4, -0.8, 0.5);
                    $positions[] = $playerPosition->add(0.6, -0.8, 0.5);
                    $positions[] = $playerPosition->add(-0.6, -0.8, 0.5);
                    $positions[] = $playerPosition->add(0.6, -1, 0.5);
                    $positions[] = $playerPosition->add(-0.6, -1, 0.5);
                    break;
            }

            $optimizer = $this->getOptimizer();
            $world = $player->getWorld();

            foreach ($positions as $pos) {
                $optimizer->addParticle($particle, $pos, $world);
            }
        }
    }

    public function getName(): string
    {
        return 'Wings';
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.particle_wings';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/dragons_breath', $callable);
    }
}