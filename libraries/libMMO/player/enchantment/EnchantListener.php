<?php
/**
 *   _ _ _     __  __ __  __  ____
 *  | (_) |   |  \/  |  \/  |/ __ \
 *  | |_| |__ | \  / | \  / | |  | |
 *  | | | '_ \| |\/| | |\/| | |  | |
 *  | | | |_) | |  | | |  | | |__| |
 *  |_|_|_.__/|_|  |_|_|  |_|\____/
 *
 * Copyright (C) 2016-2024 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy, TobiasDev, TwistedAsylumMC, Drew, larryTheCoder, Studgi
 */
declare(strict_types=1);

namespace libMMO\player\enchantment;

use Exception;
use GlobalLogger;
use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use libMMO\event\EntityArmorChangeEvent;
use libMMO\item\CustomItemManager;
use libMMO\item\CustomItemRegistry;
use libMMO\item\enchantment\chances\DetonationEnchantment;
use libMMO\item\enchantment\chances\EntanglementEnchantment;
use libMMO\item\enchantment\chances\GrappleEnchantment;
use libMMO\item\enchantment\chances\PoisonEnchantment;
use libMMO\item\enchantment\chances\ThorEnchantment;
use libMMO\item\enchantment\CustomEnchantment;
use libMMO\item\enchantment\EscapeEnchantment;
use libMMO\item\enchantment\GuardianAngelEnchantment;
use libMMO\item\enchantment\PermanentEffectEnchantment;
use libMMO\item\item\CustomEnchantedBookItem;
use libMMO\MMOPlugin;
use libMMO\player\enchantment\projectile\TripleShotArrow;
use libMMO\player\MMOEffectManager;
use libMMO\player\MMOPlayer;
use libMMO\player\PlayerData;
use libMMO\utils\BaseListener;
use libMMO\utils\RomanNumbers;
use libMMO\utils\Utils;
use libVanilla\sound\ThunderSound;
use NetherGames\NGEssentials\item\SimpleCustomItem;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory as PMInventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemEnchantmentTagRegistry as TagRegistry;
use pocketmine\item\enchantment\ItemEnchantmentTags as Tags;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\DoorBumpSound;
use pocketmine\world\sound\FizzSound;
use pocketmine\world\sound\PopSound;
use NetherGames\NGEssentials\player\PlayerData as NGEssPlayerData;

class EnchantListener extends BaseListener
{
    /** @var int */
    public static int $grappleEnchChance = 35;

    /** @var int[] */
    private static array $defaultIds = [];
    /** @var array<string, float> */
    private array $timeout = [];

    private function differentiateItems(Item $itemClicked, Item $itemClickedWith): array
    {
        if (empty(self::$defaultIds)) {
            self::$defaultIds = [CustomItemRegistry::POWER_SHARD()->getTypeId(), CustomItemRegistry::LUCKY_SHARD()->getTypeId()];
        }

        $itemClickedEnch = $itemClicked instanceof CustomEnchantedBookItem;
        $itemClickedWithEnch = $itemClickedWith instanceof CustomEnchantedBookItem;
        $itemClickedWithShard = in_array($itemClickedWith->getTypeId(), self::$defaultIds);
        $itemClickedShard = in_array($itemClicked->getTypeId(), self::$defaultIds);

        if ($itemClickedEnch && $itemClickedWithShard) {
            return [$itemClicked, $itemClickedWith];
        } else if ($itemClickedWithEnch && $itemClickedShard) {
            return [$itemClickedWith, $itemClicked];
        } else if ($itemClickedEnch && !$itemClickedWith->isNull()) {
            return [$itemClickedWith, $itemClicked];
        } else if ($itemClickedWithEnch && !$itemClicked->isNull()) {
            return [$itemClicked, $itemClickedWith];
        }

        return [VanillaItems::AIR(), VanillaItems::AIR()];
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority MONITOR
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $actions = array_values($event->getTransaction()->getActions());

        /** @var MMOPlayer $player */
        $player = $event->getTransaction()->getSource();

        $primaryAction = $actions[0] ?? null;
        $secondaryAction = $actions[1] ?? null;
        if (!$primaryAction instanceof SlotChangeAction || !$secondaryAction instanceof SlotChangeAction) {
            return;
        }

        /**
         * @var Item $itemClicked
         * @var Item $itemClickedWith
         */
        [$itemClicked, $itemClickedWith] = $this->differentiateItems(clone $primaryAction->getSourceItem(), $primaryAction->getTargetItem());

        $itemClickedCopy = clone $itemClicked;

        if ($itemClicked->getCount() > 1) {
            GlobalLogger::get()->warning("Player " . $player->getName() . " tried to stack an item with a quantity of > 1, " . $itemClicked->getCount());
            return;
        }

        if (!$itemClicked->isNull() && $itemClickedWith instanceof CustomEnchantedBookItem) {
            if (count($itemClickedWith->getEnchantments()) === 0) {
                return;
            }

            $enchantmentInfo = $itemClickedWith->getCustomBlockData();
            if ($enchantmentInfo === null || $enchantmentInfo->getString('Type', '') !== 'Specific' || !Utils::hasTag($enchantmentInfo, 'SuccessChance')) {
                return;
            }

            $enchantmentRegistry = AvailableEnchantmentRegistry::getInstance();
            $tagRegistry = TagRegistry::getInstance();

            $enchanted = false;
            foreach ($itemClickedWith->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();

                if ($enchantmentRegistry->isAvailableForItem($type, $itemClicked) || ($type === VanillaEnchantments::UNBREAKING() && $tagRegistry->isTagArrayIntersection([Tags::SWORD], $itemClicked->getEnchantmentTags()))) {
                    $level = $enchantment->getLevel();
                    $existing = $itemClicked->getEnchantment($type);
                    if ($existing !== null && $existing->getLevel() > $level) {
                        continue;
                    }
                    $itemClicked->addEnchantment(new EnchantmentInstance($type, $level));
                    $enchanted = true;
                }
            }

            if ($enchanted) {
                $lore = [];
                $lore[] = '';
                foreach ($itemClicked->getEnchantments() as $enchantment) {
                    if ($enchantment->getType() instanceof CustomEnchantment) {
                        $lore[] = TextFormat::RESET . EnchantmentUtils::getColorCodeForRarity($enchantment->getType()->getRarity()) . $enchantment->getType()->getName() . ' ' . RomanNumbers::getRomanNumber($enchantment->getLevel());
                    }
                }
                $itemClicked->setLore($lore);
                $event->cancel();

                if (mt_rand(1, 100) > $enchantmentInfo->getInt('SuccessChance')) {
                    $secondaryAction->getInventory()->setItem($secondaryAction->getSlot(), VanillaItems::AIR());
                    $primaryAction->getInventory()->setItem($primaryAction->getSlot(), $itemClickedCopy);
                    $player->getWorld()->addSound($player->getPosition(), new FizzSound(), [$player]);
                    return;
                }

                $primaryAction->getInventory()->setItem($primaryAction->getSlot(), $itemClicked);
                $secondaryAction->getInventory()->setItem($secondaryAction->getSlot(), VanillaItems::AIR());
                $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound(), [$player]);
            } else {
                $player->getWorld()->addSound($player->getPosition(), new DoorBumpSound(), [$player]);
            }
        } elseif ($itemClicked instanceof CustomEnchantedBookItem && in_array($itemClickedWith->getTypeId(), self::$defaultIds)) {
            $enchantmentInfo = $itemClicked->getCustomBlockData();
            $luckyShard = $itemClickedWith->getCustomBlockData();
            if ($enchantmentInfo === null || $enchantmentInfo->getString('Type', '') !== 'Specific' || !Utils::hasTag($enchantmentInfo, 'SuccessChance') || !Utils::hasTag($enchantmentInfo, 'Power') || !Utils::hasTag($enchantmentInfo, 'Objective') || $luckyShard === null || !Utils::hasTag($luckyShard, 'Increase')) {
                return;
            }

            $itemCount = $itemClickedWith->getCount();

            if (Utils::hasTag($luckyShard, 'PowerShard')) {
                $power = $enchantmentInfo->getInt('Power');
                $objective = $enchantmentInfo->getInt('Objective');

                // Enchanting system revised final 7 Aug 2021, this code will perform some sanity check
                // in case the power exceeds its objective threshold and the enchantment level is beyond acceptable
                // levels (in this case, the maximum level of an enchantment that is currently set).
                $enchantment = $itemClicked->getEnchantments()[array_key_first($itemClicked->getEnchantments())];

                if ($power >= $objective && $enchantment->getLevel() >= EnchantmentManager::getMaxEnchantmentLevel($enchantment->getType())) {
                    $player->getWorld()->addSound($player->getPosition(), new DoorBumpSound(), [$player]);
                    return;
                }
                $event->cancel();

                // Decrement item count until we reached item count 0.
                for (; $itemCount > 0; $itemCount--) {
                    if ($power >= $objective && $enchantment->getLevel() >= EnchantmentManager::getMaxEnchantmentLevel($enchantment->getType())) {
                        break;
                    }

                    // The real objective of the book, we simply do not use the given objective variable given
                    // in case the enchantment has exceeded beyond its objective.
                    // (Imagine the enchantment levels jumped from level 1 to 3)
                    $ceObjective = $enchantment->getLevel() * 5000;

                    $power += $luckyShard->getInt('Increase');

                    // In case we had residue power, we keep iterating this function until
                    // there were no residue left for us.
                    while ($power >= $ceObjective) {
                        $power = $power - $ceObjective;
                        $ceObjective = $objective = ($enchantment->getLevel() + 1) * 5000;

                        $itemClicked->removeEnchantment($enchantment->getType(), $enchantment->getLevel());
                        if (($enchantment->getLevel() + 1) >= EnchantmentManager::getMaxEnchantmentLevel($enchantment->getType())) {
                            $itemClicked->addEnchantment($enchantment = new EnchantmentInstance($enchantment->getType(), EnchantmentManager::getMaxEnchantmentLevel($enchantment->getType())));

                            $objective = $enchantment->getLevel() * 5000;
                            $power = $objective;
                            break;
                        }

                        $itemClicked->addEnchantment($enchantment = new EnchantmentInstance($enchantment->getType(), $enchantment->getLevel() + 1));
                    }
                }

                $enchantmentInfo->setInt('Power', $power);
                $enchantmentInfo->setInt('Objective', $objective);
                $itemClicked->setCustomBlockData($enchantmentInfo);
                EnchantmentUtils::updateLore($itemClicked);
            } else if (Utils::hasTag($luckyShard, 'LuckyShard')) {
                $success = $enchantmentInfo->getInt('SuccessChance');
                if ($success === 100) {
                    $player->getWorld()->addSound($player->getPosition(), new DoorBumpSound(), [$player]);
                    return;
                }

                $event->cancel();
                for (; $itemCount > 0 && $success < 100; $itemCount--) {
                    $increase = $luckyShard->getInt('Increase');
                    $success = min($success + $increase, 100);
                }

                $enchantmentInfo->setInt('SuccessChance', $success);
                $itemClicked->setCustomBlockData($enchantmentInfo);
                EnchantmentUtils::updateLore($itemClicked);
            } else {
                GlobalLogger::get()->critical("Prismarine shard was used and validated but no specific actions was found.");

                return;
            }

            $primaryAction->getInventory()->setItem($primaryAction->getSlot(), $itemClicked);
            $secondaryAction->getInventory()->setItem($secondaryAction->getSlot(), $itemClickedWith->setCount($itemCount));
            $player->getWorld()->addSound($player->getPosition(), new PopSound(), [$player]);
        } elseif (!$itemClicked->isNull() && $itemClickedWith->getTypeId() === ItemTypeIds::DYE) {
            $scroll = $itemClickedWith->getCustomBlockData();

            if (count($itemClicked->getEnchantments()) === 0 || EnchantmentManager::isItemExcluded($itemClicked) || $scroll === null || !Utils::hasTag($scroll, 'Increase')) {
                return;
            }

            $randomEnchantment = $itemClicked->getEnchantments()[array_rand($itemClicked->getEnchantments())];
            $itemClicked->removeEnchantment($randomEnchantment->getType());

            $book = CustomItemManager::getEnchantedBook($scroll->getInt('Increase'), new EnchantmentInstance($randomEnchantment->getType(), $randomEnchantment->getLevel()));

            $lore = [];
            $lore[] = '';
            foreach ($itemClicked->getEnchantments() as $enchantment) {
                if ($enchantment->getType() instanceof CustomEnchantment) {
                    $lore[] = TextFormat::RESET . EnchantmentUtils::getColorCodeForRarity($enchantment->getType()->getRarity()) . $enchantment->getType()->getName() . ' ' . RomanNumbers::getRomanNumber($enchantment->getLevel());
                }
            }
            $itemClicked->setLore(count($lore) > 1 ? $lore : []);
            $event->cancel();

            $primaryAction->getInventory()->setItem($primaryAction->getSlot(), $itemClicked);
            $secondaryAction->getInventory()->setItem($secondaryAction->getSlot(), VanillaItems::AIR());

            $player->getInventory()->addItem($book);

            $player->getWorld()->addSound($player->getPosition(), new PopSound(), [$player]);
        }
    }

    /**
     * @param EntityArmorChangeEvent $event
     *
     * @priority MONITOR
     */
    public function onArmorChange(EntityArmorChangeEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof MMOPlayer) {
            $oldItem = $event->getOldItem();
            $newItem = $event->getNewItem();

            /** @var MMOEffectManager $effects */
            $effects = $player->getEffects();

            foreach ($oldItem->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();

                if ($type instanceof PermanentEffectEnchantment) {
                    foreach ($type->getEffect($enchantment->getLevel()) as $effect) {
                        $effects->removePermanent($effect);
                    }
                }
            }

            foreach ($newItem->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();
                if ($type instanceof PermanentEffectEnchantment) {
                    foreach ($type->getEffect($enchantment->getLevel()) as $effect) {
                        $effects->addPermanent($effect);
                    }
                }
            }
        }
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority MONITOR
     * @throws Exception
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();

        $level = 0;
        $numOfTries = 1;
        $item = $event->getItem();
        if (($enchant = $item->getEnchantment(EnchantmentManager::BOOM())) !== null) {
            if ($enchant->getLevel() >= mt_rand(1, 400)) {
                $selection = new Selection();
                $air = VanillaBlocks::AIR();

                $brokenBlock = $event->getBlock();

                $world = $brokenBlock->getPosition()->getWorld();
                $lowestCornerBlock = $brokenBlock->getPosition()->subtract(1, 1, 1);
                for ($x = $lowestCornerBlock->getX(); $x <= $lowestCornerBlock->getX() + 2; $x++) {
                    for ($y = $lowestCornerBlock->getY(); $y <= $lowestCornerBlock->getY() + 2; $y++) {
                        for ($z = $lowestCornerBlock->getZ(); $z <= $lowestCornerBlock->getZ() + 2; $z++) {
                            if (!$world->isInWorld($x, $y, $z) || $world->getBlockAt($x, $y, $z) === VanillaBlocks::BEDROCK()) {
                                continue;
                            }

                            if ($this->canModifyBlocks($event->getPlayer(), $x, $y, $z)) {
                                $selection->add($x, $y, $z, $air);
                                $numOfTries++;
                            }
                        }
                    }
                }

                AsyncBlockManager::executeSet($selection, $event->getPlayer()->getWorld());
            }
        }

        if (($enchant = $item->getEnchantment(EnchantmentManager::DETECT())) !== null) {
            $level = $enchant->getLevel();
            $numOfTries++;
        }

        $this->doRandomCrate($player, $numOfTries, $level);
    }

    protected function doRandomCrate(Player $player, int $maxTries, int $level): void
    {
        $maxLevel = $level > 0 ? ($level === 1 ? 200 : ($level === 2 ? 150 : 100)) : 250;

        for ($i = 0; $i < $maxTries; $i++) {
            if (mt_rand(1, $maxLevel) === mt_rand(1, $maxLevel)) {
                $player->getWorld()->addSound($player->getLocation()->asVector3(), new BlazeShootSound());

                $crate = $this->getPlugin()->getCrateManager()->getRandomCrates($player);

                $this->getPlugin()->getPlayerData()->increaseKey($player, $crate);

                $player->sendTitle(' ', MMOPlugin::getPrefix() . TextFormat::GRAY . sprintf("You've found a %s Crate Key " . TextFormat::GRAY . "while mining!", TextFormat::YELLOW . $this->getPlugin()->getCrateManager()->getCrateName($crate)), 0, 60, 20);
            }
        }
    }

    /**
     * @param EntityShootBowEvent $event
     *
     * @priority MONITOR
     */
    public function onBowShoot(EntityShootBowEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $velocity = EnchantmentManager::VELOCITY();
        if (($instance = $event->getBow()->getEnchantment($velocity))) {
            $player = $event->getEntity();
            if ($player instanceof Player) {
                if (EnchantmentManager::hasCooldown($player, $velocity)) {
                    $event->cancel();
                    return;
                }

                // 1.3 seconds bow shoot cooldown when Velocity is on the bow.
                EnchantmentManager::addCooldown($player, $velocity, 26);
            }

            $event->setForce($event->getForce() + (1 + $instance->getLevel()));
        }

        if ($event->getBow()->hasEnchantment(EnchantmentManager::TRIPLE_SHOT())) {
            $player = $event->getEntity();
            $proj = $event->getProjectile();

            $arrow = new TripleShotArrow(Location::fromObject($proj->getPosition()->subtract(0.5, 0, 0), $proj->getWorld(), $proj->getLocation()->getYaw(), $proj->getLocation()->getPitch()), $player, false);
            $arrow->setMotion($proj->getMotion()->multiply($event->getForce()));
            $arrow->setPickupMode(Arrow::PICKUP_NONE);
            $arrow->spawnToAll();

            $arrow = new TripleShotArrow(Location::fromObject($proj->getPosition()->subtract(0, 0, 0.5), $proj->getWorld(), $proj->getLocation()->getYaw(), $proj->getLocation()->getPitch()), $player, false);
            $arrow->setMotion($proj->getMotion()->multiply($event->getForce()));
            $arrow->setPickupMode(Arrow::PICKUP_NONE);
            $arrow->spawnToAll();
        }
    }

    /**
     * @param EntityEffectAddEvent $event
     * @priority HIGHEST
     */
    public function onEffectAddEvent(EntityEffectAddEvent $event): void
    {
        $entity = $event->getEntity();
        if (!($entity instanceof MMOPlayer)) {
            return;
        }

        if (
            ($immunityInstance = $this->getEnchantInstance($entity->getArmorInventory()->getContents(), EnchantmentManager::IMMUNITY())) !== null
            && in_array(($event->getEffect()->getType()), [
                VanillaEffects::SLOWNESS(),
                VanillaEffects::MINING_FATIGUE(),
                VanillaEffects::NAUSEA(),
                VanillaEffects::BLINDNESS(),
                VanillaEffects::HUNGER(),
                VanillaEffects::WEAKNESS(),
                VanillaEffects::POISON(),
                VanillaEffects::WITHER(),
                VanillaEffects::FATAL_POISON(),
                VanillaEffects::LEVITATION()
            ])
        ) {
            $maxLevel = EnchantmentManager::getMaxEnchantmentLevel(EnchantmentManager::IMMUNITY());
            $level = $immunityInstance->getLevel();
            $rand = mt_rand($level, $maxLevel);
            $playerName = $entity->getName();

            if ($rand === $maxLevel) {
                $event->cancel();

                if (isset($this->timeout[$playerName]) && microtime(true) - $this->timeout[$playerName] < 1) {
                    return;
                }

                $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have become immune to the effects of the attack.');
                $this->timeout[$playerName] = microtime(true);
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @priority HIGHEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($event->getCause() === EntityDamageEvent::CAUSE_DROWNING && $entity instanceof MMOPlayer) {
            $headSlot = $entity->getArmorInventory()->getItem(ArmorInventory::SLOT_HEAD);

            $mermaid = EnchantmentManager::MERMAID();
            if ($headSlot->hasEnchantment($mermaid)) {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @priority HIGHEST
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Living) {
            $damager = $event->getDamager();

            if ($entity instanceof MMOPlayer) {
                // This should work against other entities attack.
                if ($entity->getHealth() - $event->getFinalDamage() <= 3) {
                    foreach ($entity->getArmorInventory()->getContents() as $item) {
                        if (($item->hasEnchantment(EnchantmentManager::GUARDIAN_ANGEL())) && !EnchantmentManager::hasCooldown($entity, EnchantmentManager::GUARDIAN_ANGEL())) {
                            $entity->setHealth($entity->getMaxHealth());

                            EnchantmentManager::addCooldown($entity, EnchantmentManager::GUARDIAN_ANGEL(), GuardianAngelEnchantment::EFFECT_COOLDOWN);
                            break;
                        }
                    }
                }

                // Endurance Enchantment [TESTED: YES]
                if (($instance = $this->getEnchantInstance($entity->getArmorInventory()->getContents(), EnchantmentManager::ENDURANCE())) !== null) {
                    if ($entity->getHealth() <= 5) {
                        $entity->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 2, $instance->getLevel()));
                    }
                }

                // Evasion Enchantment [TESTED: NO]
                if ($damager instanceof Player) {
                    $evasion = EnchantmentManager::EVASION();
                    if (($instance = $this->getEnchantInstance($entity->getArmorInventory()->getContents(), $evasion)) !== null
                        && $evasion->happens(2 * $instance->getLevel())) {

                        $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You evaded the attack');
                        $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'Your opponent evaded the attack.');

                        $event->cancel();
                    }

                    // Karma Enchantment [TESTED: NO]
                    $karma = EnchantmentManager::KARMA();
                    if (($instance = $this->getEnchantInstance($entity->getArmorInventory()->getContents(), $karma)) !== null
                        && $karma->happens(2 * $instance->getLevel())) {

                        $damage = $event->getFinalDamage();
                        $damager->setHealth($damager->getHealth() - $damage);
                        $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'Your opponent reflected the attack.');
                        $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You reflected the attack.');
                        $event->cancel();
                    }
                }
            }

            if ($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                if ($damager instanceof Player) {
                    $item = $damager->getInventory()->getItemInHand();

                    // Ignore custom items
                    if ($item instanceof SimpleCustomItem) {
                        return;
                    }

                    // Replenish Enchantment [TESTED: NO]
                    $replenish = EnchantmentManager::REPLENISH();
                    if ($item->hasEnchantment($replenish)) {
                        $hungerManager = $damager->getHungerManager();
                        if ($hungerManager->getFood() !== $hungerManager->getMaxFood()) {
                            $hungerManager->setFood($hungerManager->getFood() + 1);
                        }
                    }

                    $enchantment = EnchantmentManager::POISON();
                    if ((($instance = $item->getEnchantment($enchantment)) !== null) && $enchantment->happens(3 * $instance->getLevel())) {
                        $entity->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), PoisonEnchantment::POISON_DURATION * 20, $instance->getLevel()));

                        $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You poisoned your opponent.');
                        if ($entity instanceof Player) {
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have been poisoned.');
                        }
                    }

                    $enchantment = EnchantmentManager::THOR();
                    if (($item->getEnchantment($enchantment)) !== null && $enchantment->happens(ThorEnchantment::THOR_CHANCE)) {
                        $pos = $entity->getPosition();
                        $world = $entity->getWorld();
                        $viewers = $world->getViewersForPosition($pos);
                        [$fpsPlayers, $viewers] = $this->getPlugin()->getEssentials()->getPlayerManager()->splitFPSPlayers($viewers);
                        $world->addSound($pos, new ThunderSound(), $fpsPlayers);

                        NetworkBroadcastUtils::broadcastPackets($viewers, [
                            AddActorPacket::create(
                                $actorId = Entity::nextRuntimeId(),
                                $actorId,
                                EntityIds::LIGHTNING_BOLT,
                                $pos,
                                null,
                                0,
                                0,
                                0,
                                0,
                                [],
                                [],
                                new PropertySyncData([], []),
                                [],
                                null
                            )
                        ]);

                        $event->setModifier(ThorEnchantment::THOR_DAMAGE + $event->getModifier(EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS), EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);
                    }

                    if ($damager instanceof MMOPlayer) {
                        if ($item->hasEnchantment(EnchantmentManager::COMBO())) {
                            $lastCause = $entity->getLastDamageCause();
                            if ($damager->getHitRate() > 0 && $lastCause instanceof EntityDamageByEntityEvent) {
                                $damaged = $lastCause->getDamager();

                                if ($damaged !== $damager) {
                                    $damager->getEffects()->remove(VanillaEffects::STRENGTH());
                                    $damager->resetHitRate();
                                }
                            }

                            $damager->increaseHit();

                            if (($effects = $damager->getEffects()->get(VanillaEffects::STRENGTH())) === null || $effects->getDuration() < 5 * 20) {
                                if ($damager->getHitRate() > 3) {
                                    $damager->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 5 * 20, 1));
                                } else if ($damager->getHitRate() > 1) {
                                    $damager->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 5 * 20));
                                }
                            }
                        } else if ($entity instanceof MMOPlayer) {
                            if (($effects = $entity->getEffects()->get(VanillaEffects::STRENGTH())) === null || $effects->getDuration() < 5 * 20) {
                                $entity->getEffects()->remove(VanillaEffects::STRENGTH());
                            }
                        }
                    }

                    if ($entity instanceof Player) {
                        $decay = EnchantmentManager::DECAY();
                        if (($instance = $item->getEnchantment($decay)) !== null
                            && ($damage = mt_rand(0, 10) * $instance->getLevel()) > 4) {

                            foreach ($entity->getArmorInventory()->getContents() as $armor) {
                                if ($armor instanceof Durable) {
                                    $armor->applyDamage($damage);
                                }
                            }
                        }

                        // Debilitate Enchantment [TESTED: NO]
                        $debilitate = EnchantmentManager::DEBILITATE();
                        if (($instance = $item->getEnchantment($debilitate)) !== null
                            && $debilitate->happens(2 * $instance->getLevel())) {

                            $entity->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 5 * 20, 1));
                            $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 5 * 20, 1));

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You debilitated your opponent.');
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have been debilitated.');
                        }

                        // Pilfer Enchantment [TESTED: YES]
                        $pilfer = EnchantmentManager::PILFER();
                        if (($instance = $item->getEnchantment($pilfer)) !== null
                            && $pilfer->happens(2 * $instance->getLevel())
                            && $this->getPlugin()->getPlayerData()->getInt($entity, PlayerData::PLAYER_MONEY) < 10000) {

                            $amount = mt_rand(100, 5000);

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You have robbed $amount coins from your opponent.");
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You have been robbed of $amount coins.");

                            $economyManager = $this->getPlugin()->getEconomyManager();
                            $economyManager->increasePlayerMoney($damager->getName(), $amount);
                            $economyManager->reducePlayerMoney($entity->getName(), $amount);
                        }

                        // Dizzy Enchantment [TESTED: NO]
                        $dizzy = EnchantmentManager::DIZZY();
                        if (($instance = $item->getEnchantment($dizzy)) !== null
                            && $dizzy->happens(3 * $instance->getLevel())) {

                            $entity->getEffects()->add(new EffectInstance(VanillaEffects::NAUSEA(), 20 * 20, $instance->getLevel() * 15));
                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have struck your opponent with dizziness.');
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You are now suffering from dizziness.');
                        }

                        // Vampire Enchantment [TESTED: NO]
                        $vampire = EnchantmentManager::VAMPIRE();
                        if (($instance = $item->getEnchantment($vampire)) !== null
                            && $vampire->happens(15)) {

                            $damage = 0.5 * $instance->getLevel();

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You robbed ' . $damage . ' heart from your opponent.');
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have been robbed of ' . $damage . ' heart.');

                            $damager->setHealth($damager->getHealth() + $damage);
                            $entity->setHealth($entity->getHealth() - $damage);
                        }

                        // Molten Enchantment [TESTED: NO]
                        $molten = EnchantmentManager::MOLTEN();
                        if (($instance = $item->getEnchantment($molten)) !== null
                            && $molten->happens(2 * $instance->getLevel())) {

                            $entity->setOnFire(10);

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You set your opponent on fire.');
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have been set on fire.');
                        }

                        // Swipe Enchantment [TESTED: NO]
                        $swipe = EnchantmentManager::SWIPE();
                        if (($instance = $item->getEnchantment($swipe)) !== null
                            && $entity->getXpManager()->getCurrentTotalXp() > 1000
                            && $swipe->happens(2 * $instance->getLevel())) {

                            $amount = mt_rand(100, 1000);

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You robbed $amount XP from your opponent.");
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You have $amount robbed of {$entity->getXpManager()->getCurrentTotalXp()} XP.");

                            $damager->getXpManager()->onPickupXp($amount);
                            $entity->getXpManager()->subtractXp($amount);
                        }

                        $famine = EnchantmentManager::FAMINE();
                        if (($instance = $item->getEnchantment($famine)) !== null
                            && $famine->happens(2 * $instance->getLevel())) {

                            $chance = $instance->getLevel() * 2;

                            $dH = $damager->getHungerManager();
                            $eH = $entity->getHungerManager();

                            $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You robbed $chance hunger from your opponent.");
                            $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You have been robbed of $chance hunger.");

                            $damagerFood = $dH->getFood() + $chance;
                            if ($damagerFood > $dH->getMaxFood()) {
                                $damagerFood = $dH->getMaxFood();
                            }
                            $dH->setFood($damagerFood);

                            $entityFood = $eH->getFood() - $chance;
                            if ($entityFood < 0) {
                                $entityFood = 0;
                            }
                            $eH->setFood($entityFood);
                        }
                    }
                }
            } else if ($event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE && $damager instanceof MMOPlayer) {
                $item = $damager->getInventory()->getItemInHand();

                // Ignore custom blocks
                if ($item instanceof SimpleCustomItem) {
                    return;
                }

                $detonation = EnchantmentManager::DETONATION();
                if (($instance = $item->getEnchantment($detonation)) !== null) {
                    /** @var DetonationEnchantment $enchantment */
                    $enchantment = $instance->getType();

                    if ($enchantment->happens(10)) {
                        $event->setBaseDamage($event->getBaseDamage() + 1.5);
                        $entity->getWorld()->addParticle($entity->getPosition(), new HugeExplodeSeedParticle());
                        $entity->getWorld()->broadcastPacketToViewers($entity->getPosition(), LevelSoundEventPacket::nonActorSound(LevelSoundEvent::EXPLODE, $entity->getPosition()->asVector3(), false));
                    }
                }

                $entanglement = EnchantmentManager::ENTANGLEMENT();
                if ($entity instanceof MMOPlayer && ($instance = $item->getEnchantment($entanglement)) !== null) {
                    /** @var EntanglementEnchantment $enchantment */
                    $enchantment = $instance->getType();

                    if ($enchantment->happens(2 * $instance->getLevel())) {
                        $damager->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You entangled your opponent.');
                        $entity->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . 'You have been entangled.');

                        $entity->setNoClientPredictions();
                        $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entity): void {
                            if (!$entity->isClosed()) {
                                $entity->setNoClientPredictions(false);
                            }
                        }), 100);

                        $event->cancel();
                    }
                }

                $frostyArrows = EnchantmentManager::FROSTY_ARROWS();
                if (($level = $item->getEnchantmentLevel($frostyArrows)) > 0 && $frostyArrows->happens(15)) {
                    $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 3 * 20, $level - 1));

                    $damager->sendPopup(TextFormat::RED . 'Your opponent has been slowed.');
                    if ($entity instanceof MMOPlayer) {
                        $entity->sendPopup(TextFormat::RED . 'You have been slowed.');
                    }
                }

                $grapple = EnchantmentManager::GRAPPLE();
                if (($instance = $item->getEnchantment($grapple)) !== null) {
                    /** @var GrappleEnchantment $enchantment */
                    $enchantment = $instance->getType();

                    if ($enchantment->happens(self::$grappleEnchChance)) {
                        $entityPos = $entity->getPosition();
                        $damagerPos = $damager->getPosition();

                        $damager->setMotion($entityPos->subtractVector($damagerPos)->multiply(Utils::getGrapplingSpeed($entityPos->distanceSquared($damagerPos))));
                    }
                }
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     * @priority MONITOR
     */
    public function onPlayerUseItem(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        // Ignore custom items
        if ($item instanceof SimpleCustomItem) {
            return;
        }

        $escape = EnchantmentManager::ESCAPE();
        if ($item->hasEnchantment($escape)) {
            if (!EnchantmentManager::hasCooldown($player, $escape)) {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 5 * 20, 1));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 5 * 20, 1));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 5 * 20, 4));

                EnchantmentManager::addCooldown($player, $escape, EscapeEnchantment::EFFECT_COOLDOWN);
            } else {
                $player->sendPopup(MMOPlugin::getPrefix() . TextFormat::RED . "You can't do that right now. Time left: " . TextFormat::WHITE . date('i:s', EnchantmentManager::getCooldown($player, $escape)));
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * @priority MONITOR
     */
    public function onPlayerJoinEvent(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();

        $player->getInventory()->getListeners()->add(new class($player) implements InventoryListener {
            public function __construct(private readonly Player $player)
            {
            }

            public function onSlotChange(PMInventory $inventory, int $slot, Item $oldItem): void
            {
                if (!($inventory instanceof PlayerInventory)) {
                    return;
                }

                EnchantListener::handleItemChange($this->player, $inventory->getItemInHand());
            }

            public function onContentChange(PMInventory $inventory, array $oldContents): void
            {
                if (!($inventory instanceof PlayerInventory)) {
                    return;
                }

                EnchantListener::handleItemChange($this->player, $inventory->getItemInHand());
            }
        });

        $player->getArmorInventory()->getListeners()->add(new class($player) implements InventoryListener {
            public function __construct(private readonly Player $player)
            {
            }

            public function onSlotChange(PMInventory $inventory, int $slot, Item $oldItem): void
            {
                $player = $this->player;

                $healthLevel = $player->getMaxHealth();
                $currentItem = $inventory->getItem($slot);

                if ($oldItem->hasEnchantment(EnchantmentManager::TANK())) {
                    $healthLevel -= $oldItem->getEnchantmentLevel(EnchantmentManager::TANK());
                }

                if ($currentItem->hasEnchantment(EnchantmentManager::TANK())) {
                    $healthLevel += $currentItem->getEnchantmentLevel(EnchantmentManager::TANK());
                }

                if ($player->getMaxHealth() !== $healthLevel) {
                    $canReset = $player->getHealth() == $player->getMaxHealth() && $player->isAlive();

                    $player->setMaxHealth($healthLevel);
                    if ($canReset) {
                        $player->setHealth($healthLevel);
                    }
                }
            }

            public function onContentChange(PMInventory $inventory, array $oldContents): void
            {
                $player = $this->player;

                // Tank Enchantment [TESTED: YES]
                $healthLevel = 20;
                foreach ($player->getArmorInventory()->getContents() as $item) {
                    if (($instance = $item->getEnchantment(EnchantmentManager::TANK())) !== null) {
                        $healthLevel += $instance->getLevel();
                    }
                }

                if ($player->getMaxHealth() !== $healthLevel) {
                    $canReset = $player->getHealth() == $player->getMaxHealth() && $player->isAlive();

                    $player->setMaxHealth($healthLevel);
                    if ($canReset) {
                        $player->setHealth($healthLevel);
                    }
                }
            }
        });
    }

    /**
     * @param PlayerItemHeldEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        self::handleItemChange($player, $event->getItem());
    }

    public static function handleItemChange(Player $player, Item $currentItem): void
    {
        /** @var MMOEffectManager $effects */
        $effects = $player->getEffects();

        $instance = $currentItem->getEnchantment(EnchantmentManager::MINER());

        // https://timings.pmmp.io/?id=306844
        // Array is being created. Possibly create a static array?
        foreach (EnchantmentManager::MINER()->getEffect() as $effect) {
            if ($effects->hasPermanent($effect->getType())) {
                $effects->removePermanent($effect, true);
            }

            if ($instance !== null) {
                $effects->addPermanent($effect->setAmplifier($instance->getLevel() - 1));
            }
        }
    }

    /**
     * @param Item[] $contents
     * @param Enchantment $enchantment
     * @return EnchantmentInstance|null
     */
    public function getEnchantInstance(array $contents, Enchantment $enchantment): ?EnchantmentInstance
    {
        foreach ($contents as $item) {
            if (($instance = $item->getEnchantment($enchantment)) !== null) {
                return $instance;
            }
        }
        return null;
    }

    protected function canModifyBlocks(Player $player, int $x, int $y, int $z): bool
    {
        return true;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        unset($this->timeout[$event->getPlayer()->getName()]);
    }
}
