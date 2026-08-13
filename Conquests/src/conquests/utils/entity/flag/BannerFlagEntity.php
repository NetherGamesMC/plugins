<?php

declare(strict_types=1);

namespace conquests\utils\entity\flag;

use pocketmine\block\utils\BannerPatternLayer;
use pocketmine\block\utils\BannerPatternType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\WallBanner;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;

class BannerFlagEntity extends BaseFlagEntity
{
    /** @var WallBanner */
    private WallBanner $banner;

    public function __construct(Location $location, CompoundTag $nbt)
    {
        parent::__construct($location, $nbt);

        $this->setInvisible(true);
    }

    public function showNametag(): void
    {
        parent::showNametag();

        $this->setInvisible(false);
    }

    public function hideNametag(): void
    {
        parent::hideNametag();

        $this->setInvisible(true);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::VINDICATOR;
    }

    public function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $baseColor = $this->getOwningTeam()->getDyeColor();
        $otherColor = DyeColor::ORANGE;

        $banner = VanillaBlocks::WALL_BANNER();
        $banner->setColor($baseColor);
        $banner->setPatterns([
            new BannerPatternLayer(BannerPatternType::SMALL_STRIPES, $otherColor),
            new BannerPatternLayer(BannerPatternType::DIAGONAL_UP_LEFT, $baseColor),
            new BannerPatternLayer(BannerPatternType::TRIANGLES_BOTTOM, $otherColor),
            new BannerPatternLayer(BannerPatternType::BORDER, $otherColor),
        ]);
        $this->banner = $banner;
    }

    protected function sendSpawnPacket(Player $player): void
    {
        parent::sendSpawnPacket($player);

        $networkSession = $player->getNetworkSession();

        $wrapper = ItemStackWrapper::legacy(ItemStack::null());
        $pk = MobArmorEquipmentPacket::create($this->id, $wrapper, ItemStackWrapper::legacy($networkSession->getTypeConverter()->coreItemStackToNet($this->banner->asItem())), $wrapper, $wrapper, $wrapper);
        $networkSession->sendDataPacket($pk);
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setFloat(EntityMetadataProperties::SCALE, 0.0000000000001);
    }
}