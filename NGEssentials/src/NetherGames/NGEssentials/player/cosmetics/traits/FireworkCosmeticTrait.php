<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
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

namespace NetherGames\NGEssentials\player\cosmetics\traits;

use InvalidArgumentException;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\data\bedrock\FireworkRocketTypeIdMap;
use pocketmine\entity\Location;
use pocketmine\entity\object\FireworkRocket;
use pocketmine\entity\object\FireworkRocket as FireworkEntity;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\utils\Utils as PMUtils;
use pocketmine\world\Position;
use RuntimeException;
use function mt_rand;

/**
 * @mixin Cosmetic
 */
trait FireworkCosmeticTrait
{
    private const FIREWORK_KEY = 'firework';
    private const FIREWORK_TYPE_KEY = 'type';

    protected function getFirework(CosmeticDataEntry $entry, Position $pos): ?FireworkRocket
    {
        if (($world = $pos->getWorld())->isInLoadedTerrain($pos)) {
            $rocketTypeId = $entry->data[self::FIREWORK_KEY][self::FIREWORK_TYPE_KEY] ?? throw new InvalidArgumentException("Invalid firework type");
            $type = FireworkRocketTypeIdMap::getInstance()->fromId($rocketTypeId) ?? throw new RuntimeException("Invalid firework type $rocketTypeId");

            return new FireworkEntity(Location::fromObject($pos, $world, PMUtils::getRandomFloat() * 360, 90), 20 + mt_rand(0, 12), [
                new FireworkRocketExplosion($type, [Utils::getRandomDyeColor()], [], false, false)
            ]);
        }

        return null;
    }

    protected function isFireworkCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::FIREWORK_KEY]);
    }
}