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

namespace NetherGames\NGEssentials\player\cosmetics\types\game;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use NetherGames\NGEssentials\player\cosmetics\traits\EntityCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use pocketmine\player\Player;

class ShopkeepersCosmetic extends Cosmetic
{
    use EntityCosmeticTrait {
        getEntityId as public;
    }

    public function getName(): string
    {
        return 'Shopkeeper';
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.shopkeeper';
    }

    /**
     * @param Player[] $players
     */
    public function get(array $players): ?string
    {
        return ($entry = $this->getRandomSelectedEntry($players)) === null ? null : $this->getEntityId($entry->getDataEntry());
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/bedwars', $callable);
    }
}