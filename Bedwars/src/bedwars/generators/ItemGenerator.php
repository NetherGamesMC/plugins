<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace bedwars\generators;

use NetherGames\NGEssentials\entity\custom\CustomFakeMovingBlock;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\utils\TextFormat;
use function str_repeat;

class ItemGenerator extends Generator
{
    /** @var Item */
    private Item $item;
    /** @var int */
    private int $tier = 1;
    /** @var FloatingText */
    private FloatingText $floatingText;
    /** @var CustomFakeMovingBlock */
    private CustomFakeMovingBlock $customFakeBlock;

    public function __construct(Location $location, Item $item)
    {
        parent::__construct($location, AxisAlignedBB::one()->expand(1, 2, 1)->offset($location->getX(), $location->getY(), $location->getZ()));

        $this->item = $item;
        $this->time = $this->getSpawnTime();

        $entityManager = NGEssentials::getInstance()->getEntityManager();
        $entityManager->addEntity($this->floatingText = new FloatingText(new Location($location->getX(), $location->getY() + 4, $location->getZ(), $location->getWorld(), 0.0, 0.0), TextFormat::YELLOW . 'Tier ' . TextFormat::RED . str_repeat('I', $this->tier)));
        $entityManager->addEntity($this->customFakeBlock = new CustomFakeMovingBlock(new Location($location->getX(), $location->getY() + 3, $location->getZ(), $location->getWorld(), 0.0, 0.0), $this->getBlock()));
    }

    public function getSpawnTime(): int
    {
        return match ($this->getItem()->getTypeId()) {
            ItemTypeIds::EMERALD => match ($this->tier) {
                1 => 66,
                2 => 50,
                default => 30,
            },
            ItemTypeIds::DIAMOND => match ($this->tier) {
                1 => 31,
                2 => 23,
                default => 12,
            },
            ItemTypeIds::GOLD_INGOT => 4,
            ItemTypeIds::IRON_INGOT => 1,
            default => 5,
        };
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getBlock(): Block
    {
        return $this->item->getTypeId() === ItemTypeIds::EMERALD ? VanillaBlocks::EMERALD() : VanillaBlocks::DIAMOND();
    }

    public function tick(): void
    {
        if ($this->time === 0) {
            $this->dropItem(clone $this->item, false);

            $this->time = $this->getSpawnTime();
        }

        $this->getFloatingText()->setText($this->getBoard());

        $this->time--;
    }

    public function getFloatingText(): FloatingText
    {
        return $this->floatingText;
    }

    public function getBoard(): string
    {
        $board = '';

        switch ($this->item->getTypeId()) {
            case ItemTypeIds::EMERALD:
                $board .= TextFormat::BOLD . TextFormat::GREEN . 'Emerald';
                break;
            case ItemTypeIds::DIAMOND:
                $board .= TextFormat::BOLD . TextFormat::AQUA . 'Diamond';
                break;
        }

        if ($this->time > 1) {
            $board .= "\n" . TextFormat::YELLOW . 'Spawns in ' . TextFormat::RED . $this->time . TextFormat::YELLOW . ' seconds';
        } else {
            $board .= "\n" . TextFormat::YELLOW . 'Spawns in ' . TextFormat::RED . 1 . TextFormat::YELLOW . ' second';
        }

        return $board;
    }

    public function maxCapacity(Item $item): int
    {
        return match ($this->tier) {
            1 => 4,
            2 => 6,
            default => 8,
        };
    }

    public function getCustomFakeBlock(): CustomFakeMovingBlock
    {
        return $this->customFakeBlock;
    }

    /**
     * @param int $tier
     * @phpstan-param array<int, TypeConverter> $typeConverters
     * @phpstan-param array<int, list<ClientboundPacket>> $packets
     * @return void
     */
    public function setTier(int $tier, array $typeConverters, array &$packets): void
    {
        $this->tier = $tier;

        $floatingText = $this->getFloatingText();
        $floatingText->setTitle(TextFormat::YELLOW . 'Tier ' . TextFormat::RED . str_repeat('I', $this->tier));
        $floatingText->setText('');

        foreach ($typeConverters as $key => $typeConverter) {
            if (isset($packets[$key])) {
                $packets[$key][] = $floatingText->getMetadataPacket($typeConverter);
            } else {
                $packets[$key] = [$floatingText->getMetadataPacket($typeConverter)];
            }
        }

        $floatingText->setText($this->getBoard());
    }

    public function getTime(): int
    {
        return $this->time;
    }
}