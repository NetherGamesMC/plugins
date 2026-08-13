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

namespace NetherGames\NGEssentials\player\cosmetics\types\game\cage;

use Closure;
use InvalidArgumentException;
use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use NetherGames\NGEssentials\player\cosmetics\traits\BlockCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\traits\EntityCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use NetherGames\NGEssentials\player\cosmetics\utils\Cage;
use NetherGames\NGEssentials\player\cosmetics\utils\CageEntity;
use NetherGames\NGEssentials\player\cosmetics\utils\SingleBlockCageGenerator;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\StainedGlass;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\world\World;
use function array_filter;
use function array_map;
use function count;

abstract class CagesCosmetic extends Cosmetic
{
    use EntityCosmeticTrait;
    use BlockCosmeticTrait;

    /** @var array<string, Selection> */
    private array $cageCache = [];
    /** @var array<int, Cage[]> */
    private array $cages = [];

    abstract public function getSize(): int;

    protected function generateCage(?CosmeticDataEntry $entry): Selection
    {
        return match (true) {
            $entry === null => SingleBlockCageGenerator::generateCage(
                VanillaBlocks::GLASS(),
                $this->getSize()
            ),
            $this->isBlockCosmeticEntry($entry) => SingleBlockCageGenerator::generateCage(
                $this->getBlock($entry),
                $this->getSize(),
                function (Block $block) use ($entry): Block {
                    return match ($entry->id) {
                        100 => $block instanceof StainedGlass ? $block->setColor(Utils::getRandomDyeColor()) : throw new InvalidArgumentException('Block is not an instance of StainedGlass'),
                        default => $block,
                    };
                }
            ),
            default => throw new InvalidArgumentException('Invalid cosmetic entry'),
        };
    }

    public function getCage(array $players, Location $center, ?int $data = null): Cage
    {
        return $this->getCageByEntry($this->getRandomSelectedEntry($players)?->getDataEntry($data), $center);
    }

    public function getCageByEntry(?CosmeticDataEntry $entry, Location $center): Cage
    {
        $center = Location::fromObject($center->floor(), $center->getWorld(), $center->getYaw(), $center->getPitch());

        if ($entry === null) {
            return new Cage(
                $this->cageCache[''] ??= $this->generateCage($entry),
                $center
            );
        }

        $isEntityCosmetic = $this->isEntityCosmeticEntry($entry);

        return new Cage(
            $this->cageCache[$entry->getHash()] ??= $this->generateCage($entry),
            $center,
            $isEntityCosmetic ? $this->getEntityId($entry) : null,
            $isEntityCosmetic ? $this->getAnimation($entry, 'spawn') : null
        );
    }

    /**
     * @param Cage[] $cages
     */
    public function spawnCages(World $world, array $cages, bool $onlyAir = false, ?Closure $onFinish = null): void
    {
        if (count($cages) === 0) {
            return;
        }

        $selection = new Selection();

        /** @var CageEntity[] $cageEntities */
        $cageEntities = array_filter(array_map(static function (Cage $cage) use ($selection): ?CageEntity {
            return $cage->spawnCage($selection);
        }, $cages));

        if (count($cageEntities) > 0) {
            $this->handler->getPlugin()->getEntityManager()->addEntities($world, $cageEntities);
        }

        if ($onlyAir) {
            AsyncBlockManager::executeReplace($selection, [VanillaBlocks::AIR()], $world, $onFinish);
        } else {
            AsyncBlockManager::executeSet($selection, $world, $onFinish);
        }

        $this->cages[$world->getId()] = $cages;
    }

    public function runSpawnAnimation(World $world): void
    {
        if (count($cages = $this->cages[$world->getId()] ?? []) === 0) {
            return;
        }

        $packets = array_filter(array_map(fn(Cage $cage) => $cage->getSpawnAnimation(), $cages));

        if (count($packets) > 0) {
            NetworkBroadcastUtils::broadcastPackets(
                $world->getPlayers(),
                $packets
            );
        }
    }

    public function despawnCages(World $world): void
    {
        if (count($cages = $this->cages[$world->getId()] ?? []) === 0) {
            return;
        }

        $selection = new Selection();

        /** @var CageEntity[] $cageEntities */
        $cageEntities = array_filter(array_map(static function (Cage $cage) use ($selection): ?CageEntity {
            return $cage->despawnCage($selection);
        }, $cages));

        if (count($cageEntities) > 0) {
            $this->handler->getPlugin()->getEntityManager()->removeEntities($world, $cageEntities);
        }

        AsyncBlockManager::executeSet($selection, $world);
        unset($this->cages[$world->getId()]);
    }
}