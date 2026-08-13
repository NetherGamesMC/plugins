<?php

declare(strict_types=1);

namespace survivalgames\chest;

use NetherGames\NGEssentials\entity\custom\FloatingText;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\entity\Location;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use survivalgames\SGArena;
use function count;
use function date;
use function shuffle;

class ChestManager
{
    private const CHEST_REFILL_TIME = 4 * 60;

    /** @var FloatingText[] */
    private array $chestTimers = [];
    /** @var bool[] */
    private array $filledChests = [];
    /** @var SGArena */
    private SGArena $arena;
    /** @var bool */
    private bool $lastRefill = false;

    public function __construct(SGArena $arena)
    {
        $this->arena = $arena;
    }

    /**
     * Called when a player opens a chest.
     *
     * @param ChestInventory $chest
     */
    public function openChest(ChestInventory $chest): void
    {
        $pos = $chest->getHolder();
        $hash = World::blockHash($pos->getX(), $pos->getY(), $pos->getZ());

        if (!isset($this->chestTimers[$hash]) && !$this->lastRefill) {
            $this->chestTimers[$hash] = $timer = new FloatingText(new Location($pos->getX() + 0.5, $pos->getY() + 1, $pos->getZ() + 0.5, $pos->getWorld(), 0.0, 0.0), TextFormat::GREEN . '--:--');
            $timer->getMetadata()->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 0);
            $timer->getMetadata()->setFloat(EntityMetadataProperties::SCALE, 0.75);
            $timer->getMetadata()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 1.2);
            $timer->getMetadata()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.95);

            $this->getArena()->getPlugin()->getEssentials()->getEntityManager()->addEntity($timer);
        }

        if (!isset($this->filledChests[$hash])) {
            $this->filledChests[$hash] = false;

            $this->fillChest($chest);
        }
    }

    private function getArena(): SGArena
    {
        return $this->arena;
    }

    /**
     * @param ChestInventory $inventory
     * @return void
     *
     * TODO: Item pools should be considered as a way to clean this up
     */
    public function fillChest(ChestInventory $inventory): void
    {
        // Clear the chest's inventory
        $inventory->clearAll();

        $arena = $this->getArena();
        // Any chest within 15 blocks of the arena's spawn (the middle of the map) is considered to be a middle chest
        $isMiddle = $inventory->getHolder()->maxPlainDistance($arena->getWorld()->getSpawnLocation()) <= 15;

        // Select the pools based on if it's a middle chest or not
        $table = $isMiddle ? LootTableType::MIDDLE() : LootTableType::REGULAR();
        $contents = $table->roll();
        // Pad the contents array with empty slots
        $contents = array_pad($contents, $inventory->getSize(), VanillaItems::AIR());
        // Shuffle the contents array
        shuffle($contents);

        $inventory->setContents($contents);
    }

    public function closeChest(ChestInventory $inventory): void
    {
        if (!$this->arena->isNormal()) {
            return;
        }

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

        if ($this->getArena()->isNormal()) {
            $entityManager = $this->getArena()->getPlugin()->getEssentials()->getEntityManager();
            foreach ($this->getChestTimers() as $hash => $timer) {
                if ($lastRefill) {
                    $entityManager->removeEntity($timer);
                } else {
                    $timer->setTitle(TextFormat::GREEN . '--:--');
                    $timer->setText('');

                    $timer->updateNametag();
                    $timer->updateMetadata();
                }
            }

            foreach ($this->getFilledChests() as $hash => $open) {
                if ($open) {
                    World::getBlockXYZ($hash, $x, $y, $z);

                    $this->broadcastChestEventPacket(new Position($x, $y, $z, $this->arena->getWorld()), false);
                }
            }
        }

        if ($lastRefill) {
            $this->chestTimers = [];
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
        if ($this->lastRefill) {
            return -1;
        }

        return self::CHEST_REFILL_TIME - $timePassed;
    }
}
