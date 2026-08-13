<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author CortexPE
 *
 */
declare(strict_types=1);

namespace libVanilla\item;

use libVanilla\entity\animation\CrossbowChargedAnimation;
use libVanilla\entity\object\CrossbowArrow;
use libVanilla\event\EntityShootCrossbowEvent;
use libVanilla\sound\CrossbowLoadingEndSound;
use libVanilla\sound\CrossbowLoadingMiddleSound;
use libVanilla\sound\CrossbowLoadingStartSound;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemUseResult;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CompletedUsingItemPacket;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;

// todo: fireworks?
// https://minecraft.fandom.com/wiki/Crossbow
class Crossbow extends Durable
{
    private const TAG_CHARGED_ITEM = "chargedItem";

    public function getMaxDurability(): int
    {
        return 464;
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }

    public function getFuelTime(): int
    {
        return 200;
    }

    public function setChargedItem(Item $item): void
    {
        if ($item->isNull()) {
            $this->getNamedTag()->removeTag(self::TAG_CHARGED_ITEM);
            return;
        }
        $this->getNamedTag()->setTag(self::TAG_CHARGED_ITEM, $item->nbtSerialize(-1));
    }

    public function isCharged(): bool
    {
        return $this->getNamedTag()->getTag(self::TAG_CHARGED_ITEM) !== null;
    }

    public function getChargeTime(): int
    {
        return 25 - ($this->getEnchantmentLevel(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::QUICK_CHARGE)) * 5);
    }

    private function getMultishotLevel(): int
    {
        return $this->getEnchantmentLevel(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::MULTISHOT));
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult
    {
        if (!$this->isCharged()) {
            $arrow = VanillaItems::ARROW();
            $inventory = match (true) {
                $player->getOffHandInventory()->contains($arrow) => $player->getOffHandInventory(),
                $player->getInventory()->contains($arrow) => $player->getInventory(),
                default => null
            };

            if ($player->hasFiniteResources() && $inventory === null) {
                return ItemUseResult::FAIL();
            }

            $duration = $player->getItemUseDuration() + 1;
            $requiredChargeTime = $this->getChargeTime();
            $requiredChargeTime -= (int)($player->getNetworkSession()->getPing() / 50); // 1 tick = 50ms
            if ($duration === 0) {
                $player->broadcastSound(new CrossbowLoadingStartSound());
                return ItemUseResult::NONE();
            } elseif ($duration < $requiredChargeTime) {
                // todo: I don't think this gets triggered... I'm speculating it needs a separate timer
                $player->broadcastSound(new CrossbowLoadingMiddleSound());
                return ItemUseResult::NONE();
            } else {
                $player->broadcastSound(new CrossbowLoadingEndSound());

                $this->setChargedItem($arrow);
                $player->getInventory()->setItemInHand($this);

                if ($player->hasFiniteResources() && !$this->hasEnchantment(VanillaEnchantments::INFINITY())) {
                    //TODO: tipped arrows are still consumed when Infinity is applied
                    $inventory?->removeItem($arrow);
                }

                $player->getNetworkSession()->sendDataPacket(CompletedUsingItemPacket::create($this->getTypeId(), CompletedUsingItemPacket::ACTION_UNKNOWN));
                $player->broadcastAnimation(new CrossbowChargedAnimation($player));

                return ItemUseResult::SUCCESS();
            }
        }
        if (!$this->shoot($player)) {
            return ItemUseResult::FAIL();
        }
        if ($player->hasFiniteResources()) {
            $this->applyDamage(1 + ($this->getMultishotLevel() * 2));
        }
        $this->setChargedItem(VanillaItems::AIR());
        return ItemUseResult::SUCCESS();
    }

    protected function createCrossbowArrow(int $offset, int $pierceCount, Living $source): CrossbowArrow
    {
        $sourceLocation = $source->getLocation();
        $sourceLocation->y += $source->getEyeHeight();

        $sourceLocation->yaw += $offset * 10;
        $y = -sin(deg2rad($sourceLocation->pitch));
        $xz = cos(deg2rad($sourceLocation->pitch));
        $x = -$xz * sin(deg2rad($sourceLocation->yaw));
        $z = $xz * cos(deg2rad($sourceLocation->yaw));
        $motion = (new Vector3($x, $y, $z))->normalize()->multiply(5);

        // arrows are inverted idk why mojang decided to do it like this
        $sourceLocation->yaw = ($sourceLocation->yaw > 180 ? 360 : 0) - $sourceLocation->yaw;
        $sourceLocation->pitch *= -1;

        $arrow = new CrossbowArrow($sourceLocation, $source, $pierceCount);
        $arrow->setPickupMode(Arrow::PICKUP_CREATIVE);
        $arrow->setMotion($motion);

        if (($punchLevel = $this->getEnchantmentLevel(VanillaEnchantments::PUNCH())) > 0) {
            $arrow->setPunchKnockback($punchLevel);
        }
        if (($powerLevel = $this->getEnchantmentLevel(VanillaEnchantments::POWER())) > 0) {
            $arrow->setBaseDamage($arrow->getBaseDamage() + (($powerLevel + 1) / 2));
        }
        if ($this->hasEnchantment(VanillaEnchantments::FLAME())) {
            $arrow->setOnFire(intdiv($arrow->getFireTicks(), 20) + 100);
        }
        return $arrow;
    }

    public function shoot(Living $source): bool
    {
        $piercingLevel = $this->getEnchantmentLevel(EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::PIERCING));

        $mainArrow = $this->createCrossbowArrow(0, $piercingLevel, $source);
        $mainArrow->setPickupMode(Arrow::PICKUP_ANY);
        $entities = [$mainArrow];

        $multiShot = $this->getMultishotLevel();
        for ($i = 1; $i <= $multiShot; $i++) {
            $entities[] = $this->createCrossbowArrow($i, $piercingLevel, $source);
            $entities[] = $this->createCrossbowArrow(-$i, $piercingLevel, $source);
        }

        ($ev = new EntityShootCrossbowEvent($source, $this, $entities))->call();
        if ($ev->isCancelled()) {
            return false;
        }
        $allCancelled = true;
        foreach ($ev->getProjectiles() as $entity) {
            if ($entity instanceof Projectile) {
                ($projectileEv = new ProjectileLaunchEvent($entity))->call();
                if ($projectileEv->isCancelled()) {
                    $entity->flagForDespawn();
                    continue;
                }
            }
            $allCancelled = false;
            $entity->spawnToAll();
        }
        if (!$allCancelled) {
            $source->getWorld()->addSound($source->getLocation(), new BowShootSound());
        }
        return !$allCancelled;
    }
}