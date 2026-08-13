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

namespace bedwars\utils\entity;

use bedwars\Bedwars;
use bedwars\BWArena;
use bedwars\BWItems;
use bedwars\BWTeam;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class BridgeEgg extends Living
{
    private ?Block $block = null;
    private BWArena $arena;
    private ?BWTeam $team = null;
    private int $blocksPlaced;
    private const BUFFER_REMOVE_AMOUNT = 5; // Amount where the bridge egg does not return to the player as it is not sensible to return it with a number that low.

    public function __construct(Location $location, private int $blocks)
    {
        parent::__construct($location);
        $this->setCanSaveWithChunk(false);
        $this->setMovementSpeed(2.5);

        if (($arena = Bedwars::getInstance()->getArenaByWorld($this->getWorld())) instanceof BWArena) {
            $this->arena = $arena;
        } else {
            $this->terminateBridge();
            NGEssentials::getInstance()->getServer()->getLogger()->info("Something went wrong when trying to create a bridge egg at " . $location->__toString());
        }

        $this->blocksPlaced = 0;
    }

    public function setOwningEntity(?Entity $owner): void
    {
        $currentOwner = $this->getOwningEntity();

        if ($owner === $currentOwner) {
            return;
        }

        if ($currentOwner instanceof Player) {
            $this->team?->removeBridgeEgg($this, $currentOwner);
        }

        parent::setOwningEntity($owner);

        if ($owner instanceof Player) {
            $this->team = $this->arena->getTeam($owner);

            $this->block = VanillaBlocks::WOOL()->setColor($this->team->getDyeColor());

            $this->team->addBridgeEgg($this, $owner);
        }
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::TURTLE;
    }

    public function getName(): string
    {
        return "Turtle";
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        $damager = $source instanceof EntityDamageByEntityEvent ? $source->getDamager() : null;

        if (!$damager instanceof NGPlayer) {
            $this->terminateBridge();
            return;
        }

        $damagerTeam = $this->arena->getTeamNull($damager);

        $owner = $this->getOwningEntity();

        if ($damagerTeam instanceof BWTeam) {
            if ($damagerTeam === $this->team) {
                if ($owner instanceof NGPlayer) {
                    $owner->sendConditionalMessage(TextFormat::GREEN . ($damager === $owner
                            ? "Picked up bridge builder!"
                            : "Your teammate stopped your bridge"));
                }

                if ($damager !== $owner) {
                    $damager->sendConditionalMessage(TextFormat::YELLOW . "You picked up your teammate's bridge!");
                }

                $this->setOwningEntity($damager);
            } else {
                $this->blocks = 0;

                if ($owner instanceof NGPlayer) {
                    $owner->sendConditionalMessage(TextFormat::RED . "Your bridge builder was killed!");
                }

                $damager->sendConditionalMessage(TextFormat::RED . "You destroyed an enemy's bridge builder!");
            }

            $this->terminateBridge();
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.8, 2.4);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->arena->isRunning()) {
            $this->flagForDespawn();
            return $hasUpdate;
        }

        if ($this->isAlive()) {
            $direction = $this->getDirection();
            $directionBlock = $this->getWorld()->getBlock($direction);
            $listener = $this->arena->getListener();

            if (
                $directionBlock->getTypeId() !== BlockTypeIds::AIR ||
                $this->getWorld()->getBlock($this->getDirection(2)->down())->getTypeId() !== BlockTypeIds::AIR ||
                $listener->canPlaceBlock(Position::fromObject($direction->down(), $this->getWorld())) !== null ||
                $this->isCollidedHorizontally
            ) {
                $this->terminateBridge();
                return false;
            }

            $directionDownBlock = $this->getWorld()->getBlock($direction->down());
            if ($directionDownBlock->getTypeId() === BlockTypeIds::AIR) {
                $this->getWorld()->setBlock($direction->down(), $this->block ?? throw new AssumptionFailedError("Block isn't set"));
                $this->arena->getBlockCollector()->addBlock($directionDownBlock->getPosition());
                $this->blocksPlaced++;

                if (--$this->blocks <= 0) {
                    $this->flagForDespawn();
                }
            }

            $this->setMotion($this->getDirectionVector()->multiply(0.6));
            return true;
        }

        return $hasUpdate;
    }

    private function getDirection(int $step = 1): Vector3
    {
        return match ($this->getHorizontalFacing()) {
            Facing::SOUTH => $this->getPosition()->add(0, 0, $step),
            Facing::WEST => $this->getPosition()->add(-$step, 0, 0),
            Facing::NORTH => $this->getPosition()->add(0, 0, -$step),
            default => $this->getPosition()->add($step, 0, 0),
        };
    }

    /**
     * Stops the bridging and retuns the item back to the player (if necessary)
     * @return void
     */
    private function terminateBridge(): void
    {
        $this->flagForDespawn();

        $owner = $this->getOwningEntity();

        if (!$owner instanceof Player) {
            return;
        }

        // If the owner is dead/has died, they will no longer be the owner. Thus,
        // just check if there are less than 5 blocks left in the bridge egg and
        // remove it as there is no point having a bridge egg with that little 
        // number of blocks.
        if ($this->blocks < self::BUFFER_REMOVE_AMOUNT) {
            return;
        }

        $item = BWItems::BRIDGE_EGG($this->blocks);
        $owner->getInventory()->addItem($item);

        if ($this->blocksPlaced <= 3) {
            $owner->resetItemCooldown($item, 0);
        }
    }

    protected function onDispose(): void
    {
        parent::onDispose();
        $this->setOwningEntity(null);
    }
}