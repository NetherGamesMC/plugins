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

namespace NetherGames\NGEssentials\utils;

use GdImage;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\skins\SkinValidator;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function chr;
use function imagealphablending;
use function imagecolorallocatealpha;
use function imagecolorat;
use function imagecolortransparent;
use function imagecopymerge;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagefill;
use function imagesavealpha;
use function imagesetpixel;
use function imagesx;
use function imagesy;
use function intdiv;
use function ord;
use function strlen;


class SkinUtils
{

    /**
     * @deprecated
     *
     * @see Utils::getResourceContent()
     * @see SkinUtils::getTextureFromString()
     */
    public static function getTextureFromResources(string $filename): string
    {
        return self::getTextureFromString(Utils::getResourceContent($filename));
    }

    public static function getTextureFromString(string $string): string
    {
        return self::getTextureFromImage(imagecreatefromstring($string));
    }

    public static function getTextureFromImage(GdImage $img): string
    {
        $bytes = '';

        for ($y = 0; $y < imagesy($img); ++$y) {
            for ($x = 0; $x < imagesx($img); ++$x) {
                $argb = imagecolorat($img, $x, $y);
                $bytes .= chr(($argb >> 16) & 0xff) . chr(($argb >> 8) & 0xff) . chr($argb & 0xff) . chr(((~($argb >> 24)) << 1) & 0xff);
            }
        }

        return $bytes;
    }

    public static function getImageFromString(string $string): GdImage
    {
        [$width, $height] = SkinValidator::getSkinDataSize(strlen($string));

        $skinPos = 0;

        $img = imagecreatetruecolor(128, 128);
        if ($img !== false) {
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $r = ord($string[$skinPos]);
                    $skinPos++;
                    $g = ord($string[$skinPos]);
                    $skinPos++;
                    $b = ord($string[$skinPos]);
                    $skinPos++;
                    $a = 127 - intdiv(ord($string[$skinPos]), 2);
                    $skinPos++;
                    $col = imagecolorallocatealpha($img, $r, $g, $b, $a);
                    if ($width === 128 && $height === 128) {
                        imagesetpixel($img, $x, $y, $col);
                    } else {
                        imagesetpixel($img, $x * 2, $y * 2, $col);
                        imagesetpixel($img, $x * 2 + 1, $y * 2, $col);
                        imagesetpixel($img, $x * 2, $y * 2 + 1, $col);
                        imagesetpixel($img, $x * 2 + 1, $y * 2 + 1, $col);
                    }
                }
            }
            imagesavealpha($img, true);
        }

        return $img;
    }

    public static function mergeImages(GdImage $source, GdImage $destination): void
    {
        imagecolortransparent($source, imagecolorallocatealpha($source, 0, 0, 0, 127));
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        imagecopymerge($destination, $source, 0, 0, 0, 0, 128, 128, 100);
    }

    public static function saveSkin(Player $player, ?Skin $skin = null): void
    {
        // implementation required
    }

    public static function getSkin(string $player, callable $callable): void
    {
        try {
            if (($p = Server::getInstance()->getPlayerExact($player)) instanceof NGPlayer) {
                $callable($p->getSkin());
                return;
            }
        } catch (RuntimeException $e) {
            // Player is not online
        }

        $callable(new Skin('Standard_Custom', self::getTextureFromString(Utils::getResourceContent(Path::join('skins', 'default', 'steve.png')))));
    }
}