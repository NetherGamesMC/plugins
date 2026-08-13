<?php

declare(strict_types=1);

namespace survivalgames\utils\entity;

use libminigames\Arena;
use muqsit\invmenu\InvMenu;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\Inventory;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use survivalgames\SurvivalGames;

class Graveyard extends Human
{
    /** @var bool */
    public $canCollide = false;

    /** @var InvMenu|null */
    private ?InvMenu $menu = null;
    /** @var Arena|null */
    private ?Arena $arena = null;

    public function __construct(Location $location)
    {
        parent::__construct($location, SurvivalGames::$graveyard);

        $this->setNameTagAlwaysVisible(false);
        $this->setCanSaveWithChunk(false);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $onUpdate = parent::entityBaseTick($tickDiff);

        // Force xz motion to 0, now this entity wont wiggle or fishing around smh.
        $this->motion->x = 0;
        $this->motion->z = 0;

        return $onUpdate;
    }

    public function setGraveyardData(Player $player, Arena $arena): void
    {
        $items = array_merge($player->getInventory()->getContents(), $player->getArmorInventory()->getContents());

        $this->arena = $arena;

        // Use a singular chest if the contents is lesser than 27, it is confirmed
        // that NetworkStackLatencyPacket will be sent twice if the chest were doubled.
        // This is bad for players with 300ms or above.
        if (count($items) <= 27) {
            $this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
        } else {
            $this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        }
        $this->menu->getInventory()->setContents($items);
        $this->menu->setName($player->getName() . "'s Graveyard");

        $this->setNameTag($title = TextFormat::YELLOW . $player->getName() . '\'s' . TextFormat::EOL . TextFormat::GOLD . 'Grave.');

        if (empty($items)) {
            $this->setNameTag($title . TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::RED . 'Empty!');
        }

        // North direction is broken.
        switch ($player->getHorizontalFacing()) {
            case Facing::SOUTH:
            case Facing::NORTH:
                $this->location->yaw = 315;
                break;
            case Facing::WEST:
                $this->location->yaw = 140;
                break;
            case Facing::EAST:
                $this->location->yaw = 220;
                break;
        }

        $this->menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($title): void {
            if (empty($this->menu->getInventory()->getContents())) {
                $this->setNameTag($title . TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::RED . 'Empty!');
            } else {
                $this->setNameTag($title);
            }
        });
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($this->arena === null || $this->menu === null) {
            $this->flagForDespawn();
            return;
        }

        $source->cancel();
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        if ($this->arena === null || $this->menu === null) {
            $this->flagForDespawn();
        } elseif ($this->arena->isInArena($player) && !$this->arena->isSpectator($player)) {
            $this->menu->send($player);
        }

        return false;
    }

    public function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNoClientPredictions(true);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.0, 1.0);
    }
}