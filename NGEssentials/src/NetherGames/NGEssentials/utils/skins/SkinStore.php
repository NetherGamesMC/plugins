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
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils\skins;

use NetherGames\NGEssentials\utils\SkinUtils;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\Skin;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

final class SkinStore
{
    use SingletonTrait;

    /** @var Skin[] */
    private $skins = [];

    public function lazyLoad(string $parentFolder, string $basename, string $skinId, ?string $geometryName = null, ?string $capeName = null): Skin
    {
        return $this->skins[$skinId] ??= new Skin(
            $skinId,
            SkinUtils::getTextureFromString(Utils::getResourceContent(Path::join(($folder = Path::join('skins', $parentFolder)), "$basename.png"))),
            $capeName === null ? '' : SkinUtils::getTextureFromString(Utils::getResourceContent(Path::join('capes', "$basename.json"))),
            $geometryName === null ? (
            $basename === 'alex' ? 'geometry.humanoid.customSlim' : 'geometry.humanoid.custom'
            ) : $geometryName,
            $geometryName === null ? Utils::getResourceContent(Path::join('skins', 'default', 'geometry.json')) : Utils::getResourceContent(Path::join($folder, 'geometry', "$basename.json"))
        );
    }
}