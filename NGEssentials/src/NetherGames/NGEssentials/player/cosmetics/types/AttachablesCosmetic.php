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
use GdImage;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use NetherGames\NGEssentials\utils\SkinUtils;
use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\player\Player;
use function base64_decode;
use function json_encode;

class AttachablesCosmetic extends Cosmetic
{

    private const string ATTACHABLES_KEY = "attachables";
    private const string TEXTURE_KEY = "texture";
    private const string GEOMETRY_KEY = "geometry";

    /** @var array<string, array{texture: GdImage, geometry: string}> */
    private array $cache = [];

    protected function onSelect(Player $player): bool
    {
        $this->equip($player);
        return false;
    }

    public function equip(Player $player): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            /** @var NGPlayer $player */
            $player->setSkin($this->getMergedSkin($entry->getDataEntry(), $player->getOriginalSkin()));
            $player->sendSkin();
        }
    }

    protected function setSelected(Player $player, ?CosmeticEntry $entry): void
    {
        parent::setSelected($player, $entry);

        if ($entry === null) {
            $this->unequip($player);
        }
    }

    public function unequip(Player $player): void
    {
        /** @var NGPlayer $player */
        $player->setSkin($player->getOriginalSkin());
        $player->sendSkin();
    }

    public function onSkinChange(PlayerChangeSkinEvent $event): void
    {
        if (($entry = $this->getSelectedEntry($event->getPlayer())) !== null) {
            $event->setNewSkin($this->getMergedSkin($entry->getDataEntry(), $event->getNewSkin()));
        }
    }

    public function getMergedSkin(CosmeticDataEntry $entry, Skin $baseSkin): Skin
    {
        $this->cache[$hash = $entry->getHash()] ??= [
            self::TEXTURE_KEY => SkinUtils::getImageFromString(base64_decode($entry->data[self::ATTACHABLES_KEY][self::TEXTURE_KEY])),
            self::GEOMETRY_KEY => json_encode($entry->data[self::ATTACHABLES_KEY][self::GEOMETRY_KEY])
        ];

        return new Skin(
            $baseSkin->getSkinId(),
            $this->mergeTextures($baseSkin->getSkinData(), $this->cache[$hash][self::TEXTURE_KEY]),
            $baseSkin->getCapeData(),
            "geometry.humanoid.custom",
            $this->cache[$hash][self::GEOMETRY_KEY]
        );
    }

    private function mergeTextures(string $skinTexture, GdImage $attachableTexture): string
    {
        $down = SkinUtils::getImageFromString($skinTexture);

        SkinUtils::mergeImages($attachableTexture, $down);

        return SkinUtils::getTextureFromImage($down);
    }

    public function getName(): string
    {
        return 'Attachables';
    }

    public function showSkin(): bool
    {
        return true;
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/ng/tabs/attachables', $callable);
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.cape';
    }

}
