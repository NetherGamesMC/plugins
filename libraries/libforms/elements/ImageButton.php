<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms\elements;

use Closure;
use libforms\CDNUtils;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function str_replace;

class ImageButton extends Button
{
    public const IMAGE_TYPE_PATH = 0;
    public const IMAGE_TYPE_URL = 1;
    public const IMAGE_TYPE_FACE = 2;
    public const IMAGE_TYPE_FACE_HASH = 3;
    public const IMAGE_TYPE_MAP = 4;

    public function __construct(string $text, private int $imageType, private string $imageSource, ?Closure $callable = null)
    {
        parent::__construct($text, $callable);
    }

    public function getData(Player $player, string $formType = ''): array
    {
        $data = parent::getData($player, $formType);

        $imageSource = $this->getImageSource();
        $imageType = $this->getImageType();

        if ($imageType === self::IMAGE_TYPE_FACE) {
            $imageType = self::IMAGE_TYPE_URL;
            $imageSource = CDNUtils::getAvatarByName($imageSource);
        } elseif ($imageType === self::IMAGE_TYPE_FACE_HASH) {
            $imageType = self::IMAGE_TYPE_URL;
            $imageSource = CDNUtils::getAvatarByHash($imageSource);
        } elseif ($imageType === self::IMAGE_TYPE_MAP) {
            $imageType = self::IMAGE_TYPE_URL;
            $imageSource = CDNUtils::getMap($imageSource);
        }

        $data['image']['type'] = $imageType === self::IMAGE_TYPE_PATH ? 'path' : 'url';
        $data['image']['data'] = $imageSource;

        return $data;
    }

    public function getImageSource(): string
    {
        return $this->imageSource;
    }

    public function setImageSource(string $imageSource): void
    {
        $this->imageSource = $imageSource;
    }

    public function getImageType(): int
    {
        return $this->imageType;
    }

    public function setImageType(int $imageType): void
    {
        $this->imageType = $imageType;
    }

    /**
     * @param Item|Block $item
     * @return string
     */
    public static function getImagePath(Item|Block $item): string
    {
        if ($item instanceof Block) {
            /** @var Block $item */
            $parsedString = str_replace("minecraft:", "", GlobalBlockStateHandlers::getSerializer()->serializeBlock($item)->getName());
            return 'textures/blocks/' . $parsedString;
        } else {
            /** @var Item $item */
            $parsedString = str_replace("minecraft:", "", GlobalItemDataHandlers::getSerializer()->serializeType($item)->getName());
            return 'textures/items/' . $parsedString;
        }
    }
}