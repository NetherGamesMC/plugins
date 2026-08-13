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
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use pocketmine\player\Player;

class FlagsCosmetic extends Cosmetic
{
    use EntityCosmeticTrait;

    public const RED_TEAM = 0;
    public const BLUE_TEAM = 1;

    /**
     * @param Player[] $players
     * @param bool $isRedTeam
     */
    public function get(array $players, bool $isRedTeam = false): ?string
    {
        return ($entry = $this->getRandomSelectedEntry($players)) === null ? null : $this->getEntityForTeam($entry, $isRedTeam);
    }

    public function getEntityForTeam(CosmeticEntry $entry, bool $isRedTeam): string
    {
        return $this->getEntityId($entry->getDataEntry($isRedTeam ? self::RED_TEAM : self::BLUE_TEAM));
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.flag';
    }

    public function getName(): string
    {
        return 'Flag';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/conquests', $callable);
    }
}