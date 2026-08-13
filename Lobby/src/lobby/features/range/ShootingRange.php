<?php

declare(strict_types=1);

namespace lobby\features\range;

use lobby\entity\custom\GroundRangeEntity;
use lobby\entity\custom\IconMarker;
use lobby\entity\custom\RangeEntity;
use lobby\features\secret\SecretData;
use lobby\Lobby;
use lobby\utils\Items;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class ShootingRange
{
    public const FOREST_RANGE = 1;
    public const RANGE_TOKEN_UNLOCK = 10;
    public const TOKEN_SPAWN_LOCATION = [218, 55, 20];

    public const DEFAULT_TIME = 30;
    private ?NGPlayer $player = null;
    private int $points = 0;
    private int $time = self::DEFAULT_TIME;
    private ?Living $rangeEntity = null;

    public function __construct(
        private Position      $spawnPosition,
        private AxisAlignedBB $axisAlignedBB,
        private World         $world,
    )
    {
        Lobby::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(closure: function (): void {
            if ($this->getPlayer() === null || !$this->getPlayer()->isConnected()) {
                $this->reset();
                return;
            }

            if ($this->time === 30) {
                $entity = $this->getRandomEntity();
                $entity->spawnTo($this->getPlayer());

                $this->setRangeEntity($entity);
            }

            $this->time--;
            if ($this->time === 0) {
                Items::setLobbyInventory($this->getPlayer());

                $this->getPlayer()->teleport(new Vector3(223, 55, 19));
                $this->getPlayer()->sendMessage(TextFormat::GREEN . "You have finished with a score of $this->points");

                if ($this->points >= self::RANGE_TOKEN_UNLOCK && !PlayerUtils::hasUnlockedToken($this->getPlayer(), SecretData::ARCHERY)) {
                    [$x, $y, $z] = self::TOKEN_SPAWN_LOCATION;

                    $location = new Location($x, $y, $z, $this->getWorld(), 0, 0);

                    $entity = new IconMarker($location, "Archery", true);
                    $entity->addTo($this->player);
                    $entity->spawnTo($this->player);
                }

                $this->reset();
                return;
            }

            $this->getPlayer()->sendActionBarMessage(TextFormat::GREEN . "$this->time seconds remaining | Current score of " . $this->points);
        }), 20);
    }

    public function getPlayer(): ?NGPlayer
    {
        return $this->player;
    }

    public function reset(): void
    {
        $this->player = null;
        $this->points = 0;
        $this->time = self::DEFAULT_TIME;

        $entity = $this->getRangeEntity();
        if ($entity !== null && !$entity->isFlaggedForDespawn()) {
            $entity->flagForDespawn();
        }

        $this->rangeEntity = null;
    }

    public function getRangeEntity(): ?Living
    {
        return $this->rangeEntity;
    }

    public function setRangeEntity(Living $entity): void
    {
        $this->rangeEntity = $entity;
    }

    public function getRandomEntity(): ?Living
    {
        return match (mt_rand(1, 2)) {
            1 => new RangeEntity($this, $this->getRandomLocation()),
            2 => new GroundRangeEntity($this, $this->getRandomLocation(1))
        };
    }

    public function getRandomLocation(int $int = 0): Location
    {
        $axis = $this->getAxisAlignedBB();

        return new Location(mt_rand((int)$axis->minX, (int)$axis->maxX), 56 - $int, mt_rand((int)$axis->minZ, (int)$axis->maxZ), $this->getWorld(), 260, 0);
    }

    public function getAxisAlignedBB(): AxisAlignedBB
    {
        return $this->axisAlignedBB;
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function addPoint(): void
    {
        $this->points++;
    }

    public function addPlayer(NGPlayer $player): void
    {
        $this->player = $player;
        $player->teleport($this->getSpawnPosition());

        $player->getInventory()->setContents([
            4 => VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY())),
            8 => VanillaItems::ARROW(),
        ]);

        $player->getInventory()->setHeldItemIndex(4);
    }

    public function getSpawnPosition(): Position
    {
        return $this->spawnPosition;
    }

    public function removePlayer(Player $player): void
    {
        $this->player = null;

        if ($player->isConnected()) {
            Items::setLobbyInventory($player);
        }
    }
}