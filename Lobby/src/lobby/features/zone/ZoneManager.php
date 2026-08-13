<?php

declare(strict_types=1);

namespace lobby\features\zone;

use lobby\features\zone\types\AutoDiscoveredZone;
use lobby\features\zone\types\BasicDiscoverableZone;
use lobby\features\zone\types\InvisiblityZone;
use lobby\features\zone\types\PropertyChangingDiscoverableZone;
use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\player\worldfeatures\zones\ZonesManager;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\World;

class ZoneManager
{
    use BaseTrait;

    public function __construct()
    {
        $zoneManager = $this->getNGEssentials()->getPlayerManager()->getWorldFeatures()->getZonesManager();

        $world = $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();
        $spawn = $this->getNGEssentials()->getServerManager()->getSpawn();

        $boundingBox = new AxisAlignedBB(-4, World::Y_MIN, -4, 4, World::Y_MAX, 4);
        $boundingBox->offset($spawn->getX(), 0, $spawn->getZ());
        $zoneManager->addZone(new InvisiblityZone($boundingBox, $world));
        $this->registerDefaultZones($zoneManager, $world);
    }

    public function registerDefaultZones(ZonesManager $manager, World $world): void
    {
        $manager->addZone(new AutoDiscoveredZone("Leaderboards", new Location(121.5, 43, -39.5, $world, 224, 0), new AxisAlignedBB(103, 40, -57, 160, 72, -46), $world));
        $manager->addZone(new AutoDiscoveredZone("Spawn", Location::fromObject($world->getSafeSpawn(), $world, 0, 0), new AxisAlignedBB(0, 0, 0, 1, 1, 1), $world));
        $manager->addZone(new PropertyChangingDiscoverableZone(false, true, function (Player $player): void {
            foreach ($this->getPlugin()->getFeaturesManager()->getShootingRanges() as $ranges) {
                foreach ($ranges as $range) {
                    if ($range->getPlayer() === $player) {
                        $range->removePlayer($player);
                    }
                }
            }
        }, "Archery Ranges", new Location(216.5, 55, 19.5, $world, 89, 0), new AxisAlignedBB(167, 44, -27, 242, 90, 58), $world));

        $manager->addZone(new BasicDiscoverableZone("Maze", new Location(42.5, 49, -136.5, $world, 180, 0), new AxisAlignedBB(24, 43, -193, 75, 97, -123), $world));
        $manager->addZone(new BasicDiscoverableZone("Upper Village", new Location(-50.5, 55.5, -222, $world, 229, 0), new AxisAlignedBB(76, 50, -225, 227, 101, -49), $world));
        $manager->addZone(new BasicDiscoverableZone("Lower Village", new Location(111.5, 55.5, -147, $world, 213, 0), new AxisAlignedBB(76, 50, -225, 227, 101, -49), $world));
        $manager->addZone(new BasicDiscoverableZone("Harbour", new Location(-30.5, 40, -109.5, $world, 339, 0), new AxisAlignedBB(-84, 33, -126, 13, 66, -31), $world));
        $manager->addZone(new BasicDiscoverableZone("Temple", new Location(34.5, 64, 141.5, $world, 359, 0), new AxisAlignedBB(-31, 48, 95, 178, 200, 213), $world));
        $manager->addZone(new BasicDiscoverableZone("Volcano", new Location(-72.5, 40, 49.5, $world, 47, 0), new AxisAlignedBB(-355, 39, 1, -79, 250, 302), $world));
        $manager->addZone(new BasicDiscoverableZone("Mines", new Location(190.5, 69, 256.5, $world, 289, 0), new AxisAlignedBB(162, 44, 210, 251, 104, 287), $world));
        $manager->addZone(new BasicDiscoverableZone("Campsite", new Location(-180.5, 55, -63.5, $world, 144, 0), new AxisAlignedBB(-59, 55, -289, 18, 126, -244), $world));
        $manager->addZone(new BasicDiscoverableZone("Cave", new Location(-14.5, 20, -12.5, $world, 350, 0), new AxisAlignedBB(-35, 5, -33, 31, 33, 52), $world));
        $manager->addZone(new PropertyChangingDiscoverableZone(false, true, function (Player $player): void {
        }, "Secret Cave", new Location(20.5, 25, 28.5, $world, 350, 0), new AxisAlignedBB(14, 19, 29, 29, 28, 47), $world));
    }
}