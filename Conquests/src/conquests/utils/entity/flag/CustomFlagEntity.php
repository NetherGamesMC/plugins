<?php

declare(strict_types=1);

namespace conquests\utils\entity\flag;

use pocketmine\entity\Attribute;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use function array_map;

class CustomFlagEntity extends BaseFlagEntity
{
    private string $networkTypeId;

    public static function getNetworkTypeId(): string
    {
        return '';
    }

    public function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->networkTypeId = $nbt->getString('NetworkTypeId');
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setString("NetworkTypeId", $this->networkTypeId);

        return $nbt;
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $networkSession = $player->getNetworkSession();
        $networkSession->sendDataPacket(AddActorPacket::create(
            $this->getId(), //TODO: actor unique ID
            $this->getId(),
            $this->networkTypeId,
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw, //TODO: head yaw
            $this->location->yaw, //TODO: body yaw (wtf mojang?)
            array_map(function (Attribute $attr): NetworkAttribute {
                return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
            }, $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            [] //TODO: entity links
        ));

        if ($this->linkPacket !== null) {
            $networkSession->sendDataPacket($this->linkPacket);
        }
    }
}