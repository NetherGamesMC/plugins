<?php

declare(strict_types=1);


namespace libVanilla\entity\traits;

use libVanilla\entity\inventory\MonsterInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;

trait ItemInventoryTrait
{
    /** @var MonsterInventory */
    protected MonsterInventory $inventory;

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->inventory = new MonsterInventory($this);
        $syncHeldItem = function (): void {
            foreach ($this->getViewers() as $viewer) {
                if ($viewer->isConnected()) {
                    $this->syncItemInHand($viewer);
                }
            }
        };
        $this->inventory->getListeners()->add(CallbackInventoryListener::onAnyChange(static function (Inventory $unused) use ($syncHeldItem): void {
            $syncHeldItem();
        }));

        $hand = $nbt->getCompoundTag("HandItem");
        $this->inventory->setItemInHand($hand === null ? $this->getDefaultItem() : Item::nbtDeserialize($hand));
    }

    public function syncItemInHand(Player $player): void
    {
        $netId = $player->getNetworkSession()->getTypeConverter()->coreItemStackToNet($this->getInventory()->getItemInHand());

        $player->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create(
            $this->getId(),
            ItemStackWrapper::legacy($netId),
            0,
            0,
            ContainerIds::INVENTORY
        ));
    }

    public function getInventory(): MonsterInventory
    {
        return $this->inventory;
    }

    public function getDefaultItem(): Item
    {
        return VanillaItems::AIR();
    }

    public function sendSpawnPacket(Player $player): void
    {
        parent::sendSpawnPacket($player);

        $this->syncItemInHand($player);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $handItem = $this->getInventory()->getItemInHand();
        if (!$handItem->isNull()) {
            $nbt->setTag("HandItem", $handItem->nbtSerialize());
        }

        return $nbt;
    }
}