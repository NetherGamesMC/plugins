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

namespace NetherGames\NGEssentials\player\cosmetics\types;

use Closure;
use InvalidArgumentException;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\player\Player;
use function base64_decode;

class CapesCosmetic extends Cosmetic
{
    private const CAPE_KEY = 'cape';
    private const CAPE_DATA_KEY = 'data';

    public function onSelect(Player $player): bool
    {
        $this->equip($player);
        return true;
    }

    public function equip(Player $player): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            /** @var NGPlayer $player */
            $player->setSkin($this->getSkin($entry->getDataEntry(), $player->getOriginalSkin()));
            $player->sendSkin();
        }
    }

    public function onSkinChange(PlayerChangeSkinEvent $event): void
    {
        if (($entry = $this->getSelectedEntry($event->getPlayer())) !== null) {
            $event->setNewSkin($this->getSkin($entry->getDataEntry(), $event->getNewSkin()));
        }
    }

    public function getSkin(CosmeticDataEntry $entry, Skin $currentSkin): Skin
    {
        return new Skin(
            $currentSkin->getSkinId(),
            $currentSkin->getSkinData(),
            $this->getCapeData($entry),
            $currentSkin->getGeometryName(),
            $currentSkin->getGeometryData()
        );
    }

    public function remove(Player $player): void
    {
        /** @var NGPlayer $player */
        $player->setSkin($player->getOriginalSkin());
        $player->sendSkin();
    }

    private function getCapeData(CosmeticDataEntry $entry): string
    {
        return base64_decode($entry->data[self::CAPE_KEY][self::CAPE_DATA_KEY] ?? throw new InvalidArgumentException('Cape data not found'));
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.cape';
    }

    public function getName(): string
    {
        return 'Capes';
    }

    public function showSkin(): bool
    {
        return true;
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/ng/tabs/capes', $callable);
    }
}
