<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 * @noinspection RandomApiMigrationInspection
 */
declare(strict_types=1);

namespace skywars\utils;

use NetherGames\NGEssentials\entity\custom\FloatingText;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use skywars\GameTableType;
use skywars\SWArena;
use function count;
use function date;
use function shuffle;

class ChestManager
{
    /** The amount of time, in seconds, between chest refills */
    private const CHEST_REFILL_TIME = 5 * 60;

    /** @var FloatingText[] */
    private array $chestTimers = [];
    /** @var bool[] */
    private array $filledChests = [];
    private bool $lastRefill = false;
    private GameTableType $tableType;

    public function __construct(private SWArena $arena)
    {
        // Set loot tables based off of arena type
        $this->tableType = GameTableType::NORMAL();
    }

    public function setup(): void
    {
        $this->tableType = $this->arena->getType() === SWArena::TYPE_INSANE ? GameTableType::INSANE() : GameTableType::NORMAL();
    }

    public function openChest(ChestInventory $chest): void
    {
        $pos = $chest->getHolder();
        $hash = World::blockHash($pos->getX(), $pos->getY(), $pos->getZ());

        if (!isset($this->chestTimers[$hash]) && !$this->lastRefill) {
            $this->chestTimers[$hash] = $timer = new FloatingText(new Location($pos->getX() + 0.5, $pos->getY() + 1, $pos->getZ() + 0.5, $pos->getWorld(), 0.0, 0.0), TextFormat::GREEN . '--:--');

            $this->getArena()->getPlugin()->getEssentials()->getEntityManager()->addEntity($timer);
        }

        if (!isset($this->filledChests[$hash])) {
            $this->filledChests[$hash] = false;

            $this->fillChest($chest);
        }
    }

    public function getArena(): SWArena
    {
        return $this->arena;
    }

    /**
     * @param ChestInventory $inventory
     *
     * @return void
     */
    public function fillChest(ChestInventory $inventory): void
    {
        // Clear the chest's inventory
        $inventory->clearAll();

        $arena = $this->getArena();
        // Any chest within 15 blocks of the arena's spawn (the middle of the map) is considered to be a middle chest
        $isMiddle = $inventory->getHolder()->maxPlainDistance($arena->getWorld()->getSpawnLocation()) <= 15;

        // Select the loot table based on if it's a middle chest or not
        $table = $isMiddle ? $this->tableType->getMiddleTable() : $this->tableType->getSpawnTable();
        $contents = $table->roll();
        // Pad the contents array with empty slots
        $contents = array_pad($contents, $inventory->getSize(), VanillaItems::AIR());
        // Shuffle the contents array
        shuffle($contents);

        $inventory->setContents($contents);
    }

    public function closeChest(ChestInventory $inventory): void
    {
        $pos = $inventory->getHolder();
        $hash = World::blockHash($pos->getX(), $pos->getY(), $pos->getZ());
        $empty = count($inventory->getContents()) === 0;
        $exists = isset($this->chestTimers[$hash]);
        $plugin = $this->getArena()->getPlugin();

        if ($empty) {
            if ($this->lastRefill) {
                if (!$exists) {
                    $this->chestTimers[$hash] = $timer = new FloatingText(new Location($pos->getX() + 0.5, $pos->getY() + 1, $pos->getZ() + 0.5, $pos->getWorld(), 0.0, 0.0), TextFormat::RED . 'Empty!');

                    $plugin->getEssentials()->getEntityManager()->addEntity($timer);
                }
            } else {
                $timer = $this->chestTimers[$hash];

                $timer->setTitle(TextFormat::GREEN . '--:--');
                $timer->setText(TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::RED . 'Empty!');
                $timer->updateNametag();

                $timer->updateMetadata();
            }

            $plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($pos): void {
                $this->broadcastChestEventPacket($pos, true);
            }), 1);
        } elseif (!$this->lastRefill) {
            $timer = $this->chestTimers[$hash];

            $timer->setTitle(TextFormat::GREEN . '--:--');
            $timer->setText('');
            $timer->updateNametag();

            $timer->updateMetadata();
        } elseif ($exists) {
            $timer = $this->chestTimers[$hash];

            $plugin->getEssentials()->getEntityManager()->addEntity($timer);

            unset($this->chestTimers[$hash]);
        }

        $this->filledChests[$hash] = $empty;
    }

    public function broadcastChestEventPacket(Position $pos, bool $isOpen): void
    {
        $pos->getWorld()->broadcastPacketToViewers($pos, BlockEventPacket::create(
            BlockPosition::fromVector3($pos),
            1,
            $isOpen ? 1 : 0
        ));
    }

    public function tickChestTimer(int $timePassed): void
    {
        if ($timePassed === self::CHEST_REFILL_TIME) {
            $this->refillChests();

            $this->getArena()->broadcastTitle(' ', TextFormat::YELLOW . 'All chests have been refilled!', 0, 60, 20);
        } elseif ($timePassed < self::CHEST_REFILL_TIME) {
            $title = date('i:s', $this->getNextRefill($timePassed));

            foreach ($this->getArena()->getChestManager()->getChestTimers() as $timer) {
                $timer->setTitle(TextFormat::GREEN . $title);

                $timer->updateNametag();
                $timer->updateMetadata();
            }
        }
    }

    public function refillChests(bool $lastRefill = true): void
    {
        $this->lastRefill = $lastRefill;

        $entityManager = $this->getArena()->getPlugin()->getEssentials()->getEntityManager();
        foreach ($this->getChestTimers() as $timer) {
            if ($lastRefill) {
                $entityManager->removeEntity($timer);
            } else {
                $timer->setTitle(TextFormat::GREEN . '--:--');
                $timer->setText('');

                $timer->updateNametag();
                $timer->updateMetadata();
            }
        }

        if ($lastRefill) {
            $this->chestTimers = [];
        }

        foreach ($this->getFilledChests() as $hash => $open) {
            if ($open) {
                World::getBlockXYZ($hash, $x, $y, $z);

                $this->broadcastChestEventPacket(new Position($x, $y, $z, $this->arena->getWorld()), false);
            }
        }

        $this->filledChests = [];
    }

    /**
     * @return FloatingText[]
     */
    public function getChestTimers(): array
    {
        return $this->chestTimers;
    }

    /**
     * @return bool[]
     */
    public function getFilledChests(): array
    {
        return $this->filledChests;
    }

    public function getNextRefill(int $timePassed): int
    {
        return !$this->lastRefill ? self::CHEST_REFILL_TIME - $timePassed : -1;
    }
}