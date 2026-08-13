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

namespace NetherGames\NGEssentials\player\cosmetics\types\armor;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use libVanilla\VanillaPlugin;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use pocketmine\inventory\ArmorInventory;
use pocketmine\player\Player;

class ChestplatesCosmetic extends ArmorCosmetic
{
    public function __construct(int $saveId, CosmeticHandler $handler)
    {
        VanillaPlugin::ELYTRA()->register($handler->getPlugin());

        parent::__construct(ArmorInventory::SLOT_CHEST, $saveId, $handler);
    }

    public function getName(): string
    {
        return 'Chestplates';
    }

    public function getCrateAnimation(): string
    {
        return "animation.ng.lobby.crate.chestplate";
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/iron_chestplate', $callable);
    }
}