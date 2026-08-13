<?php
declare(strict_types=1);

namespace lobby\entity\custom;

use libPhysX\PhysX;
use lobby\Lobby;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\Chunk;

class CommandNPC extends Human implements ChunkLoader
{
    /** @var int */
    private int $movementTick = 0;

    public function __construct(private string $command, Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct(location: $location, skin: $skin, nbt: $nbt);

        $chunkX = $location->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $location->getFloorZ() >> Chunk::COORD_BIT_SIZE;
        $location->getWorld()->registerChunkLoader(
            loader: $this,
            chunkX: $chunkX,
            chunkZ: $chunkZ,
            autoLoad: true
        );
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                Lobby::getInstance()->getServer()->dispatchCommand($damager, $this->command);
            }
        }
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->movementTick += $tickDiff;
        $world = $this->getWorld();
        $location = $this->getLocation();

        /** @var NGPlayer[] $players */
        $players = $world->getPlayers();
        $players = array_filter($players, static function (NGPlayer $player) {
            return !$player->isInvisible();
        });

        if ($this->movementTick >= 3) {
            $closestPlayer = null;
            $closestDistance = 0;
            foreach ($players as $player) {
                $playerDistance = $location->distance($player->getLocation());

                if ($playerDistance < 25 && ($closestDistance === 0 || $closestDistance >= $playerDistance)) {
                    $closestDistance = $playerDistance;
                    $closestPlayer = $player;
                }
            }

            if ($closestPlayer !== null) {
                $rotation = PhysX::calculateRotationEulerAngle($this->getOffsetPosition($location), $closestPlayer->getEyePos());
                if ($rotation->yaw === $location->getYaw()) {
                    $this->movementTick = 0;

                    return parent::entityBaseTick($tickDiff); // Do not send anything if the rotation is unchanged
                }
                $this->setRotation($rotation->yaw, 0);
            }

            $this->movementTick = 0;
        }

        return parent::entityBaseTick($tickDiff);
    }
}