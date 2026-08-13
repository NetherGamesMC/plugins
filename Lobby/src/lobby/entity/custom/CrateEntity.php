<?php

declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\features\crate\task\CrateTask;
use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\ChunkLoader;

class CrateEntity extends Entity implements ChunkLoader
{
    use BaseTrait;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setHasGravity(false);
    }

    public static function getNetworkTypeId(): string
    {
        return "ng:lobby_crate";
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if (!$damager instanceof Player) {
                return;
            }

            $vector = $this->getPosition()->asVector3();

            $crate = $this->getPlugin()->getFeaturesManager()->getCrateHandler()->getCrate(new Vector3($vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ()));
            if ($crate !== null) {
                $ess = $this->getNGEssentials();
                $playerData = $ess->getPlayerData();

                if ($playerData->getInt($damager, PlayerData::KEYS) <= 0) {
                    $damager->sendMessage(TextFormat::RED . "You don't have enough keys!");
                } elseif (($entry = $this->getNGEssentials()->getPlayerManager()->getCosmeticHandler()->getAvailableCrateCosmetic($damager)) === null) {
                    $damager->sendMessage(TextFormat::RED . "You've already claimed all cosmetics!");
                } elseif ($playerData->getBool($damager, PlayerData::DATA_LOADED)) {
                    if ($crate->isInUse()) {
                        Translator::sendMessage($damager, "crate.inuse", Translator::TYPE_ERROR);
                        return;
                    }

                    $crate->setInUse($damager);

                    CosmeticHandler::getCosmeticById($entry->type)->give($damager, $entry);
                    $keys = $playerData->addInt($damager, PlayerData::KEYS, -1, true);
                    $ess->getServerData()->getScoreBoard()->setLine([$damager], 6, CustomIcon::KEY . "Keys: " . TextFormat::GREEN . $keys);

                    $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CrateTask($this, $crate, $entry), 10);
                }
            }
        }

        $source->cancel();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(3, 4);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0;
    }

    protected function getInitialGravity(): float
    {
        return 0;
    }
}