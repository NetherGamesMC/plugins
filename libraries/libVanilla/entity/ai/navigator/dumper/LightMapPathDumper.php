<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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

namespace libVanilla\entity\ai\navigator\dumper;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\World;

final class LightMapPathDumper implements PathDumper
{
    public function showPath(World $world, array $path): void
    {
        $length = count($path);
        foreach ($path as $i => $node) {
            $progress = $i / $length;
            $world->addParticle($node, new DustParticle(
                new Color((int)(255 * $progress), (int)(255 * $progress), (int)(255 * $progress))
            ));
        }
    }
}