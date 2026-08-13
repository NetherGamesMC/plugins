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

namespace bedwars;

use bedwars\shops\Upgrade;
use bedwars\utils\entity\BridgeEgg;
use bedwars\utils\entity\EnderDragon;
use bedwars\utils\entity\Landmine;
use bedwars\utils\entity\mob\BaseMob;
use bedwars\utils\entity\mob\Bedbug;
use bedwars\utils\entity\mob\DreamDefender;
use bedwars\utils\entity\mob\MiniSkeleton;
use bedwars\utils\entity\PrimedTNT;
use bedwars\utils\entity\Snowball;
use bedwars\utils\Forms;
use bedwars\utils\PopupTower;
use bedwars\utils\Sponge as SpongeUtils;
use bedwars\utils\Utils;
use bedwars\utils\world\Explosion;
use libminigames\ArenaListener;
use libminigames\utils\Items;
use NetherGames\NGEssentials\events\NGChatEvent;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\Air;
use pocketmine\block\BaseSign;
use pocketmine\block\Bed;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Cauldron;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\inventory\EnderChestInventory;
use pocketmine\block\Ladder;
use pocketmine\block\Liquid;
use pocketmine\block\Sponge;
use pocketmine\block\TNT;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wool;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\object\Painting;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shears;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Limits;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils as PMUtils;
use pocketmine\world\Position;
use pocketmine\world\sound\ChestCloseSound;
use pocketmine\world\sound\IgniteSound;
use pocketmine\world\sound\ThrowSound;
use WeakMap;
use function abs;
use function array_filter;
use function ceil;
use function cos;
use function count;
use function floor;
use function in_array;
use function method_exists;
use function mt_rand;
use function preg_last_error_msg;
use function preg_replace;
use function sin;
use function str_replace;
use const M_PI;

final class BWArenaListener extends ArenaListener
{
    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        if ($event->getSource() instanceof Liquid && $event->getBlock() instanceof Air) {
            return;
        }

        $event->cancel();
    }

    public function onBlockForm(BlockFormEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $event->cancel();
    }

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        /** @var NGPlayer $player */
        $player = $event->getPlayer();

        if ($this->getArena()->isRunning()) {
            if ($block instanceof Bed) {
                if (($bedTeam = $this->getArena()->getTeamByBed($block)) !== null) {
                    $team = $this->getArena()->getTeam($player);

                    if ($bedTeam->getName() === $team->getName()) {
                        $player->sendConditionalMessage(TextFormat::RED . match (mt_rand(1, 10)) {
                                1 => array_rand(array_flip([
                                    "That's uh... your bed...",
                                    "Your teammates will get mad at you for doing that.",
                                    "No, you're meant to protect me!",
                                    "Teamtrolling is a punishable offence!",
                                    "Ooo, that tickles!",
                                    "Great work! Now do that to §oother§r§c bases!",
                                    "What did you think was going to happen?",
                                    "NO! I WILL NOT BE DESTROYED!",
                                    "Maybe if you do it again, it'll work!",
                                    "Do §o/hub§r§c for an automatic victory!",
                                    "That was a little §otucked-up§r§c of you!",
                                    "That was a very pil-§olow§r§c move.",
                                    "I §obed§r§c you're gonna lose some §osleep§r§c over that one.",
                                    "Keep doing this for more dad jokes!",
                                    "Mate, I'm on your team!",
                                    "You're supposed to protect me!",
                                    "§oYou broke me firsttttt",
                                    "Let's put that practice to good use!",
                                    "Ouch! Stop that!",
                                    "Don't be shy... break some more.",
                                    "Don't keep doing this! " . ($this->getArena()->isSoloGame() ? "You will lose!" : "Instead, help your team mates!"),
                                    "Are you on a self-sabotage speedrun?",
                                    "Are you trying to play BedWars on Hardcore Mode?"
                                ])),
                                default => 'Why would you want to destroy your own bed?'
                            });
                        $event->cancel();
                    } elseif ($bedTeam->isBedAlive()) {
                        $plugin = $this->getArena()->getPlugin();

                        CosmeticHandler::BED_BREAK_SOUNDS()->run($player, $pos = $block->getPosition());
                        CosmeticHandler::BED_BREAK_EFFECTS()->run($player, $pos);

                        $bedTeam->onBedDestroy($player);
                        foreach ($bedTeam->getAlivePlayers() as $teamPlayer) {
                            $teamPlayer->sendTitle(TextFormat::BOLD . TextFormat::RED . 'BED DESTROYED!', 'You will no longer respawn!', 0, 60, 20);
                        }

                        $bedTeam->updateScoreboardEntry();

                        $this->getArena()->broadcastMessage("\n" . TextFormat::BOLD . 'BED DESTRUCTION > ' . TextFormat::RESET . str_replace(['{TEAM}', '{DAMAGER}'], [$bedTeam->getDisplayName() . ' Bed', $this->getArena()->getTeam($player)->getPlayerName($player)], $plugin->getRandomBreakMessage()), true);
                        $this->getArena()->broadcastMessage("\n", true);

                        $this->getArena()->addBedBroken($player);
                    }

                    $event->setDrops([]);
                }
            } elseif (($blockCollector = $this->getArena()->getBlockCollector())->isBreakable($pos = $block->getPosition())) {
                $team = $this->getArena()->getTeam($player);
                $blockCollector->removeBlock($pos);

                $array = [];
                foreach ($event->getDrops() as $drop) {
                    $b = $drop->getBlock();
                    if (method_exists($b, "setColor")) {
                        $b->setColor($team->getDyeColor());
                        $array[] = $block->asItem()->setCount($drop->getCount());
                    } else {
                        $array[] = $drop;
                    }
                }
                $event->setDrops($array);
            } elseif (!$this->getArena()->getGameSettings()->hasNoProtection()) {
                $player->sendConditionalMessage(TextFormat::RED . "You can't break that block.");
                $event->cancel();
            }
        } else {
            $event->cancel();
        }
    }

    public function getArena(): BWArena
    {
        /** @var BWArena $arena */
        $arena = parent::getArena();
        return $arena;
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getTransaction()->getSource();

        foreach ($event->getTransaction()->getInventories() as $inventory) {
            if ($inventory instanceof ArmorInventory) {
                $event->cancel();
            } elseif ($inventory instanceof BlockInventory) {
                foreach ($event->getTransaction()->getActions() as $action) {
                    if (($item = $action->getSourceItem())->getTypeId() === ItemTypeIds::SHEARS || $item->getTypeId() === ItemTypeIds::WOODEN_SWORD || $item instanceof Axe || $item instanceof Pickaxe) {
                        $event->cancel();
                        continue;
                    }

                    if ($item instanceof Sword) {
                        $swordCounter = count(array_filter($player->getInventory()->getContents(), fn(Item $item) => $item instanceof Sword));

                        if ($swordCounter === 0) {
                            $sword = VanillaItems::WOODEN_SWORD();
                            $sword->setUnbreakable();
                            $swordUpgradeLevel = $this->getArena()->getTeam($player)->getUpgradeLevel(Upgrade::SWORDS());
                            if ($swordUpgradeLevel > 0) {
                                $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $swordUpgradeLevel));
                            }
                            $player->getInventory()->addItem($sword);
                        }
                    }
                }
            } elseif ($inventory instanceof PlayerInventory) {
                foreach ($event->getTransaction()->getActions() as $action) {
                    if (($item = $action->getSourceItem()) instanceof Sword && $item->getTypeId() !== ItemTypeIds::WOODEN_SWORD) {
                        $inventory->removeItem(VanillaItems::WOODEN_SWORD());
                    }
                }
            }
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $inventory = $event->getInventory();
        if (!$inventory instanceof ChestInventory && !$inventory instanceof EnderChestInventory) {
            parent::onInventoryOpen($event);
            return;
        }

        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $playerInv = $player->getInventory();
        $heldItem = $playerInv->getItemInHand();

        if (
            !$this->getArena()->getGameSettings()->hasQuickDepositChests() ||
            !in_array($heldItem->getTypeId(), [
                ItemTypeIds::IRON_INGOT,
                ItemTypeIds::GOLD_INGOT,
                ItemTypeIds::EMERALD,
                ItemTypeIds::DIAMOND,
            ], true)
        ) {
            parent::onInventoryOpen($event);
            return;
        }

        $event->cancel();

        $slotIndex = $playerInv->getHeldItemIndex();

        $playerInv->setItemInHand(VanillaItems::AIR());
        $leftovers = $inventory->addItem($heldItem);

        if (!empty($leftovers)) {
            $player->sendConditionalMessage(TextFormat::RED . "Chest is full! Some items could not be deposited.");

            foreach ($leftovers as $leftover) {
                if ($playerInv->getItem($slotIndex)->getTypeId() === VanillaItems::AIR()->getTypeId()) {
                    $playerInv->setItem($slotIndex, $leftover); // $leftovers should only ever have one item
                    continue;
                }
                $playerInv->addItem($leftover);
            }
        }

        if ($inventory instanceof EnderChestInventory) {
            $this->getArena()->getTeam($player)->setEnderChestContents($player, $inventory->getContents());
        }

        $player->broadcastSound(new ChestCloseSound());
    }

    public function onInventoryClose(InventoryCloseEvent $event): void
    {
        if ($event->getInventory() instanceof EnderChestInventory) {
            $player = $event->getPlayer();
            $this->getArena()->getTeam($player)->setEnderChestContents($player, $event->getInventory()->getContents());
        }
    }

    public function onCraftItem(CraftItemEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->equals(BWItems::BEDBUG_SNOWBALL())) {
            if (!$this->getArena()->getTeam($player)->canSpawnMob()) {
                $player->sendConditionalMessage(TextFormat::RED . "Could not spawn! You're team has too many entities!");
                return;
            }

            $event->cancel();
            $location = $player->getLocation();

            $projectile = new Snowball(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
            $projectile->setMotion($event->getDirectionVector()->multiply(1.5));

            $projectileEv = new ProjectileLaunchEvent($projectile);
            $projectileEv->call();
            if ($projectileEv->isCancelled()) {
                $projectile->flagForDespawn();
            }

            $projectile->spawnToAll();

            $location->getWorld()->addSound($location, new ThrowSound());

            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }
    }

    public function onPlayerItemConsume(PlayerItemConsumeEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item->equals(BWItems::MAGIC_MILK())) {
            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::WATER_BREATHING(), 30 * 20, 0, false, true)
            );
        } elseif ($item->equals(BWItems::SWIFTNESS_POTION())) {
            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::SPEED(), 45 * 20, 1)
            );
        } elseif ($item->equals(BWItems::STRONG_LEAPING_POTION())) {
            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::JUMP_BOOST(), 45 * 20, 3)
            );
        } elseif ($item->equals(BWItems::INVISIBILITY_POTION())) {
            if ($player->getEffects()->has(VanillaEffects::LEVITATION())) {
                $player->sendConditionalMessage(TextFormat::RED . "You can not drink Invisibility if you are levitating!");
                $event->cancel();
                return;
            }

            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::INVISIBILITY(), 30 * 20, 0)
            );
        } elseif ($item->equals(BWItems::STRENGTH_POTION())) {
            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::STRENGTH(), 30 * 20)
            );
        } elseif ($item->equals(BWItems::HASTE_POTION())) {
            $player->getEffects()->add(
                new EffectInstance(VanillaEffects::HASTE(), 30 * 20, 2)
            );
        } elseif ($item->equals(BWItems::LEVITATION_POTION())) {
            if ($player->getEffects()->has(VanillaEffects::LEVITATION())) {
                $player->sendConditionalMessage(TextFormat::RED . "You are already levitating!");
                $event->cancel();
                return;
            }

            if ($player->getEffects()->has(VanillaEffects::INVISIBILITY())) {
                $player->sendConditionalMessage(TextFormat::RED . "You can not drink levitation if you are invisible!");
                $event->cancel();
                return;
            }

            $player->getEffects()->add(new EffectInstance(VanillaEffects::LEVITATION(), BWItems::LEVITATION_DURATION * 20, 1));
            $this->getArena()->broadcastMessage(TextFormat::YELLOW . $player->getNameTag() . TextFormat::RESET . " has taken flight!", true);
        } else {
            return;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item);

        $event->cancel();
        $player->resetItemCooldown($item);
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        $arena = $this->getArena();

        if ($arena->isVersus() && $item->equals(Items::getTypeSelectionAnvil())) {
            Forms::sendTypeSelector($player, $arena);
            return true;
        }

        return parent::onItemInteract($player, $item);
    }

    /**
     * @param EntityEffectAddEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityEffectAdd(EntityEffectAddEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof NGPlayer) {
            switch ($event->getEffect()->getType()) {
                case VanillaEffects::INVISIBILITY():
                    $player->setArmorInvisible();
                    Utils::sendArmour($player, false, $this->getArena()->getTeam($player)->getPlayers());
                    break;
                case VanillaEffects::LEVITATION():
                    $player->getArmorInventory()->setHelmet(BWItems::LEVITATION_HELMET());
                    break;
            }
        }
    }


    /**
     * @param EntityEffectRemoveEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityEffectRemove(EntityEffectRemoveEvent $event): void
    {
        $player = $event->getEntity();

        if ($player instanceof NGPlayer) {
            switch ($event->getEffect()->getType()) {
                case VanillaEffects::INVISIBILITY():
                    if ($player->isArmorInvisible()) {
                        $player->setArmorInvisible(false);
                        Utils::sendArmour($player, false, $player->getWorld()->getPlayers());
                    }
                    break;
                case VanillaEffects::LEVITATION():
                    $player->getXpManager()->setXpAndProgress(0, 0);
                    $player->getArmorInventory()->setContents($this->getArena()->getTeam($player)->getPermanentArmor($player));
                    break;
                case VanillaEffects::HASTE():
                    if (($level = $this->getArena()->getTeamNull($player)?->getUpgradeLevel(Upgrade::MINER())) > 0) {
                        $event->getEffect()
                            ->setDuration(Limits::INT32_MAX)
                            ->setAmplifier($level - 1)
                            ->setVisible(false);

                        $event->cancel();
                    }
                    break;
            }
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $item = $event->getItem();

        /**
         * @var WeakMap<Player, float>|null $messageLock
         */
        static $messageLock = null;

        if ($messageLock === null) {
            $messageLock = new WeakMap();
        }

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $sendMessage = static function (string $message) use ($player, $messageLock): void {
                $expiry = $messageLock[$player] ?? null;
                $now = floor(microtime(true) * 1000);

                if ($expiry === null || $expiry < $now) {
                    $player->sendConditionalMessage($message);
                    $messageLock[$player] = $now + 250;
                }
            };

            $location = $player->getLocation();

            if ($this->getArena()->getGameSettings()->hasCooldowns() && $player->hasItemCooldown($item)) {
                $timeLeft = (int)ceil(($player->getItemCooldownExpiry($item) - $player->getServer()->getTick()) / 20);
                $sendMessage(TextFormat::RED . 'You can use this item again in ' . $timeLeft . ' second' . ($timeLeft == 1 ? '' : 's') . '.');
                $event->cancel();
                return;
            }

            if ($item->equals(CustomItemRegistry::LANDMINE())) {
                $team = $this->getArena()->getTeam($player);

                $block = $event->getBlock();
                $pos = $block->getPosition()->add(0, 1, 0);

                if ($this->canPlaceBlock($pos) !== null) {
                    $sendMessage(TextFormat::RED . "You can't place a landmine here.");
                    return;
                }

                foreach ($player->getWorld()->getNearbyEntities(
                    AxisAlignedBB::one()->expand(Landmine::LANDMINE_SPACING, Landmine::LANDMINE_SPACING, Landmine::LANDMINE_SPACING)->offset($pos->getX(), $pos->getY(), $pos->getZ())
                ) as $nearbyEntity) {
                    if ($nearbyEntity instanceof Landmine && !$nearbyEntity->isFlaggedForDespawn()) {
                        $sendMessage(TextFormat::RED . "You can't place a landmine so close to another landmine.");
                        return;
                    }
                }

                $team->onLandminePlace();

                $area = [
                    $pos->getSide(Facing::SOUTH),
                    $pos->getSide(Facing::EAST),
                    $pos->getSide(Facing::SOUTH)->getSide(Facing::EAST),
                ];

                foreach ($area as $i => $vec) {
                    $areaBlock = $player->getWorld()->getBlock($vec);

                    if (!$this->canPlaceBlock($vec) || $areaBlock->getTypeId() !== BlockTypeIds::AIR || !$areaBlock->getSide(Facing::DOWN)->isSolid()) {
                        $pos = $pos->add(0.5, 0, 0.5);
                        break;
                    }

                    if ($i === 2) {
                        $pos = $pos->add(-0.15, 0, -0.15);
                    }
                }

                $projectile = new Landmine(Location::fromObject($pos, $player->getWorld()), $player);
                $projectile->setTeam($team);

                $projectileEv = new ProjectileLaunchEvent($projectile);
                $projectileEv->call();
                if ($projectileEv->isCancelled()) {
                    $projectile->flagForDespawn();
                    return;
                }

                $team->addLandmine($projectile);

                $projectile->spawnToAll();
                $location->getWorld()->addSound($location, new ThrowSound());
                $player->resetItemCooldown($item, 2 * 20);
            } elseif ($item->equals(BWItems::BRIDGE_EGG(), true, false)) {
                $pos = $event->getBlock()->getSide($event->getFace())->getPosition();

                $entity = new BridgeEgg(
                    Location::fromObject($pos->add(0.5, 0, 0.5), $player->getWorld(), $player->getLocation()->getYaw()),
                    $item->getNamedTag()->getInt(BWItems::TAG_BLOCKS, 32)
                );
                $entity->setOwningEntity($player);
                $entity->spawnToAll();
            } elseif ($item->equals(BWItems::SKELETON_ARMY_EGG(), true, false)) {
                $team = $this->getArena()->getTeam($player);
                if ($team->canSpawnMob()) {
                    $pos = $event->getBlock()->getSide($event->getFace())->getPosition();

                    for ($i = 0; $i <= 7; $i++) {
                        $entity = new MiniSkeleton(Location::fromObject($pos->add(mt_rand(-3, 3), 0, mt_rand(-3, 3)), $player->getWorld(), $player->getLocation()->getYaw()));
                        $entity->setOwningEntity($player);
                        $entity->spawnToAll();
                    }
                } else {
                    $player->sendConditionalMessage(TextFormat::RED . "Could not spawn! You're team has too many entities!");
                    return;
                }
            } elseif ($item->equals(BWItems::DEFENDER_EGG(), true, false)) {
                $team = $this->getArena()->getTeam($player);
                if ($team->canSpawnMob()) {
                    $pos = $event->getBlock()->getSide($event->getFace())->getPosition();

                    $entity = new DreamDefender(Location::fromObject($pos, $player->getWorld(), PMUtils::getRandomFloat() * 360));
                    $entity->setOwningEntity($player);
                    $entity->spawnToAll();
                } else {
                    $player->sendConditionalMessage(TextFormat::RED . "Could not spawn! You're team has too many entities!");
                    return;
                }
            } elseif (match (true) {
                ($block = $event->getBlock()) instanceof BaseSign => true,
                $block instanceof Cauldron => true,
                in_array($block->getTypeId(), [
                    BlockTypeIds::ANVIL,
                    BlockTypeIds::CRAFTING_TABLE,
                    BlockTypeIds::BREWING_STAND,
                    BlockTypeIds::ENCHANTING_TABLE,
                    BlockTypeIds::SMITHING_TABLE,
                    BlockTypeIds::FURNACE,
                    BlockTypeIds::BARREL,
                ]) => true,
                default => false
            }) {
                $event->cancel();
                return;
            } else {
                return;
            }

            $messageLock[$player] = floor(microtime(true) * 1000) + 250;

            $event->cancel();
            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }
    }

    public function onProjectileLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof EnderPearl) {
            $entity->setMotion($entity->getMotion()->multiply(2.2 / 1.5));
        }
    }

    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event): void
    {
        $block = $event->getBlockClicked();
        $pos = $block->getPosition();

        if (($error = $this->canPlaceBlock($pos)) === null) {
            $event->setItem(VanillaItems::AIR());
        } else {
            /** @var NGPlayer $player */
            $player = $event->getPlayer();

            $player->sendConditionalMessage(TextFormat::RED . $error);
            $event->cancel();
        }
    }

    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        /** @var Player $player */
        $player = $event->getEntity();
        $item = $event->getItem();

        if ($item instanceof Sword && $item->getTypeId() !== ItemTypeIds::WOODEN_SWORD) {
            $player->getInventory()->removeItem(VanillaItems::WOODEN_SWORD());
        }
    }

    public function onStructureGrow(StructureGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onPlayerChat(NGChatEvent $event): void
    {
        $player = $event->getPlayer();

        if ($this->getArena()->isSpectator($player)) {
            $event->setDisplayName(TextFormat::clean($player->getDisplayName()));
            if ($this->getArena()->isPrivateGame()) {
                $event->setRecipients($this->getArena()->getPlayers());
                $event->setPrefix('§7Spectator » ');
                $event->setStaffPrefix('§7Private Spectator Chat Relay > ');
            } else {
                $event->setRecipients($this->getArena()->getSpectators());
                $event->setPrefix('§7Dead Chat > ');
                $event->setStaffPrefix('§7Dead Chat Relay > ');
            }
            $event->setSplitter(': ');
        } else {
            $team = $this->getArena()->getTeam($player);
            $event->setDisplayName($team->getPlayerName($player));

            if (!$this->getArena()->isSoloGame() && $this->getArena()->isRunning()) {
                if (str_starts_with(TextFormat::clean($event->getMessage()), '!')) {
                    $message = preg_replace("/!/", '', $event->getMessage(), 1) ?? throw new AssumptionFailedError("preg_replace failed: " . preg_last_error_msg());
                    $event->setMessage($message);
                } else {
                    $event->setRecipients($team->getAlivePlayers());
                    $event->setPrefix($team->getColor() . "Team > ");
                    $event->setStaffPrefix(TextFormat::WHITE . "Team Chat Relay > ");
                }
            }
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($item instanceof Axe || $item instanceof Pickaxe || $item instanceof Shears) {
            $event->cancel();
        } elseif ($item instanceof Sword) {
            if ($item->getTypeId() === ItemTypeIds::WOODEN_SWORD) {
                $event->cancel();
            } else {
                $swordCounter = count(array_filter($player->getInventory()->getContents(), fn(Item $item) => $item instanceof Sword));

                if ($swordCounter <= 1) {
                    $sword = VanillaItems::WOODEN_SWORD();
                    $sword->setUnbreakable();
                    if (($swordUpgradeLevel = $this->getArena()->getTeam($player)->getUpgradeLevel(Upgrade::SWORDS())) > 0) {
                        $sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), $swordUpgradeLevel));
                    }
                    $player->getInventory()->addItem($sword);
                }
            }
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getEntity();
        $damager = $event->getDamager();

        if ($player->getEffects()->has(VanillaEffects::INVISIBILITY()) && ($damager instanceof Player || $damager?->getOwningEntity() instanceof Player)) {
            $player->getEffects()->remove(VanillaEffects::INVISIBILITY());
            $player->sendConditionalMessage(TextFormat::RED . "Your invisibility has been taken!");
        }

        if ($damager instanceof Player) {
            $team = $this->getArena()->getTeam($player);
            $level = $team->getUpgradeLevel(Upgrade::HEART_STEALER());

            $chance = match ($level) {
                1 => 8,
                2 => 5,
                default => 0
            };

            if ($chance !== 0 && mt_rand(1, $chance) === 1 && $damager->getMaxHealth() > $damager->getHealth()) {
                $gainedHearts = $event->getFinalDamage();

                if ($damager->getHealth() + $gainedHearts > $damager->getMaxHealth()) {
                    $gainedHearts = $damager->getMaxHealth() - $damager->getHealth();
                }

                if ($gainedHearts <= 0 || $player->getHealth() - $gainedHearts <= 0) {
                    return;
                }

                $player->sendJukeboxPopup("§c" . $damager->getName() . " stole " . number_format($gainedHearts, 1) . " " . CustomIcon::HEART . " from you!");
                $player->setHealth($player->getHealth() - $gainedHearts);

                $damager->sendJukeboxPopup("§a" . number_format($gainedHearts, 1) . " " . CustomIcon::HEART . " stolen!");
                $damager->setHealth($damager->getHealth() + $gainedHearts);
            }
        } elseif ($damager instanceof Bedbug) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::WITHER(), 3 * 20, 1));
        }
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $block = $event->getBlock();

        if (!$block instanceof Liquid && !$block instanceof Ladder) {
            $event->cancel();
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;

        if ($entity instanceof Player) {
            $arena = $this->getArena();
            $plugin = $arena->getPlugin();
            $gameSettings = $arena->getGameSettings();

            $team = $arena->getTeam($entity);
            $cause = $event->getCause();

            if ($damager instanceof Player && $damager->getEffects()->has(VanillaEffects::RESISTANCE())) {
                $damager->getEffects()->remove(VanillaEffects::RESISTANCE());
            }

            if ($arena->isVersus() && $arena->getType() === BWArena::TYPE_FIREBALL && $cause === EntityDamageEvent::CAUSE_FALL) {
                $event->cancel();
                return;
            }

            if ($entity->getEffects()->has(VanillaEffects::RESISTANCE())) {
                $event->cancel();
            } elseif ($event->getFinalDamage() >= $entity->getHealth() || $cause === EntityDamageEvent::CAUSE_VOID) {
                $event->cancel();
                $killedBy = null;

                if (
                    $damager !== null &&
                    in_array($cause, [
                        EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                        EntityDamageEvent::CAUSE_PROJECTILE,
                        EntityDamageEvent::CAUSE_ENTITY_EXPLOSION,
                        EntityDamageEvent::CAUSE_BLOCK_EXPLOSION
                    ], true)
                ) {
                    if ($damager instanceof Player) {
                        $killedBy = $damager;
                    } else if (($owner = $damager->getOwningEntity()) instanceof Player && $arena->isInArena($owner)) {
                        $killedBy = $owner;
                    }
                }

                if ($killedBy === null) {
                    if (($damager = $arena->getLatestActiveHitter($entity)) !== null) {
                        $killedBy = $damager;
                    } elseif (($bedDestroyer = $arena->getTeam($entity)->getActiveBedDestroyer()) !== null) {
                        $killedBy = $bedDestroyer;
                    }
                }

                if ($killedBy === null) {
                    $arena->broadcastMessage(str_replace('{PLAYER}', $team->getPlayerName($entity), $plugin->getRandomKillMessage($event->getCause())) . ($team->isBedAlive() ? '' : ' §l§bFINAL KILL!'), true);
                } else {
                    $damagerTeam = $arena->getTeam($killedBy);
                    $message = str_replace(
                        ['{PLAYER}', '{DAMAGER}'],
                        [$team->getPlayerName($entity), $damagerTeam->getPlayerName($killedBy)],
                        $arena->getPlugin()->getRandomKillMessage($cause, true)
                    );

                    if ($team->isBedAlive()) {
                        $arena->addKill($killedBy, $entity);
                        $arena->broadcastMessage($message, true);
                    } else {
                        $arena->addFinalKill($killedBy, $entity);
                        $arena->broadcastMessage($message . ' §l§bFINAL KILL!', true);
                    }
                }

                if (!$gameSettings->hasKeepInventory()) {
                    $plugin->generateDrop($entity, $killedBy);
                }

                $this->onPlayerDeath($entity, $team, in_array($cause, [
                    EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                    EntityDamageEvent::CAUSE_PROJECTILE,
                    EntityDamageEvent::CAUSE_ENTITY_EXPLOSION,
                    EntityDamageEvent::CAUSE_BLOCK_EXPLOSION,
                    EntityDamageEvent::CAUSE_FALL
                ], true));
            }
        } elseif ($entity instanceof Painting) {
            $event->cancel();
        } elseif ($damager instanceof Player) {
            if ($entity instanceof EnderDragon) {
                if ($event instanceof EntityDamageByChildEntityEvent && $event->getChild() instanceof Arrow) {
                    $entity->findNewTarget();
                    $event->setBaseDamage(0);
                } else {
                    $event->cancel();
                }
            } elseif ($entity instanceof BaseMob) {
                if ($this->getArena()->getTeam($damager) === $entity->getTeam()) {
                    $event->cancel();
                }
            }
        }
    }

    public function onPlayerDeath(Player $player, BWTeam $team, bool $spawnCorps = true): void
    {
        $gameSettings = $this->getArena()->getGameSettings();

        if (!$gameSettings->hasKeepInventory()) {
            $team->removePlayerBridgeEggs($player);
        }

        if ($team->isBedAlive()) {
            $playerInventoryContents = $gameSettings->hasKeepInventory() ? $player->getInventory()->getContents() : null;

            $this->getArena()->resetPlayer($player);

            if ($gameSettings->hasInstantRespawn()) {
                /** @var NGPlayer $player */
                $team->spawnPlayer($player, true);

                if ($playerInventoryContents !== null) {
                    $player->getInventory()->setContents($playerInventoryContents);
                }

                $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . 'RESPAWNED!', '', 0, 20, 20);
                return;
            }

            $this->getArena()->getPlugin()->getScheduler()->scheduleRepeatingTask(new class($team, $player, $playerInventoryContents) extends Task {
                private int $time;

                /**
                 * @param Item[]|null $playerInventoryContents
                 * @phpstan-param  array<int, Item>|null $playerInventoryContents
                 */
                public function __construct(
                    private readonly BWTeam $team,
                    private readonly Player $player,
                    private readonly ?array $playerInventoryContents = null
                )
                {
                    $this->time = $team->getArena()->isVersus() ? 3 : 5;
                }

                public function onRun(): void
                {
                    $arena = $this->team->getArena();
                    /** @var NGPlayer $player */
                    $player = $this->player;

                    if ($player->isClosed() || $arena->isFinishing() || !$arena->isInArena($player)) {
                        $this->getHandler()?->cancel();
                    } elseif ($this->time >= 2) {
                        $player->sendTitle(TextFormat::BOLD . TextFormat::RED . 'YOU DIED!', TextFormat::YELLOW . 'You will respawn in ' . TextFormat::RED . $this->time . TextFormat::YELLOW . ' seconds!');
                        $this->time--;
                    } elseif ($this->time === 1) {
                        $player->sendTitle(TextFormat::BOLD . TextFormat::RED . 'YOU DIED!', TextFormat::YELLOW . 'You will respawn in ' . TextFormat::RED . '1' . TextFormat::YELLOW . ' second!');
                        $this->time--;
                    } else {
                        $this->team->spawnPlayer($player, true);

                        if ($this->playerInventoryContents !== null) {
                            $player->getInventory()->setContents($this->playerInventoryContents);
                        }

                        $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . 'RESPAWNED!', '', 0, 20, 20);

                        $this->getHandler()?->cancel();
                    }
                }
            }, 20);

            $player->setGamemode(GameMode::SPECTATOR);
            if (!$spawnCorps) {
                $player->teleport($this->getArena()->getWorld()->getSpawnLocation());
            }
        } else {
            $player->sendMessage(TextFormat::RED . 'You have been eliminated!');
            $player->sendTitle('§l§cYOU DIED!', '§7You are now a spectator.', 0, 100, 20);

            foreach ($player->getEnderInventory()->getContents() as $item) {
                if (!in_array($item->getTypeId(), [ItemTypeIds::IRON_INGOT, ItemTypeIds::GOLD_INGOT, ItemTypeIds::DIAMOND, ItemTypeIds::EMERALD], true)) {
                    continue;
                }
                $team->getGenerator()?->dropItem($item, false);
            }

            $team->getArena()->addSpectator($player);

            if (!$spawnCorps) {
                $player->teleport($player->getWorld()->getSpawnLocation());
            }

            $team->checkElimination();
        }
    }

    public function onEntityExplode(EntityExplodeEvent $event): void
    {
        if ($this->getArena()->getType() === BWArena::TYPE_FIREBALL) {
            $event->setBlockList([]);
            return;
        }
    }

    public function onEntityPreExplode(EntityPreExplodeEvent $event): void
    {
        $entity = $event->getEntity();
        $event->cancel();

        $explosion = new Explosion(Position::fromObject($entity->getLocation()->add(0, $entity->getSize()->getHeight() / 2, 0), $entity->getWorld()), $event->getRadius(), $this->getArena(), $entity);
        if ($event->isBlockBreaking()) {
            $explosion->explodeA();
        }
        $explosion->explodeB();
    }

    public function canPlaceBlock(Vector3 $pos): ?string
    {
        $arena = $this->getArena();

        if (!$this->getArena()->getGameSettings()->hasNoProtection()) {
            foreach ($arena->getTeams() as $team) {
                if (!$team->canPlaceBlock($pos)) {
                    return TextFormat::RED . "You can't place blocks here.";
                }
            }
        }

        if (($worldBorder = $arena->getWorldBorder()) !== null && !$worldBorder->isVectorInXZ($pos)) {
            return TextFormat::RED . 'You have reached the map border.';
        } elseif (abs($this->getArena()->getSpawnY() - $pos->getY()) > 30) {
            return TextFormat::RED . 'You have reached the build limit.';
        }

        return null;
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $transaction = $event->getTransaction();
        $item = $event->getItem();
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $arena = $this->getArena();

        /**
         * @var Block $block
         */
        foreach ($transaction->getBlocks() as [$x, $y, $z, $block]) {
            $pos = new Position($x, $y, $z, $player->getWorld());

            if (($error = $this->canPlaceBlock($pos)) !== null) {
                $player->sendConditionalMessage($error);
            } elseif ($item->equals(BWItems::POPUP_TOWER())) {
                $item->pop();
                $player->getInventory()->setItemInHand($item);

                PopupTower::buildCompact($player, $pos, $arena);
            } elseif ($block instanceof TNT) {
                $mot = (new Random())->nextSignedFloat() * M_PI * 2;

                $tnt = new PrimedTNT(Location::fromObject($pos->add(0.5, 0, 0.5), $pos->getWorld()));
                $tnt->setFuse(80);
                $tnt->setWorksUnderwater($block->worksUnderwater());
                $tnt->setMotion(new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));

                $tnt->spawnToAll();
                $tnt->broadcastSound(new IgniteSound());

                $item->pop();
                $player->getInventory()->setItemInHand($item);
            } elseif ($arena->isVersus() && $arena->getType() === BWArena::TYPE_FIREBALL && $block instanceof Wool) {
                $arena->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($pos): void {
                    if ($pos->isValid() && $pos->getWorld()->getBlock($pos)->getTypeId() === BlockTypeIds::WOOL) {
                        $pos->getWorld()->setBlock($pos, VanillaBlocks::AIR());
                    }
                }), 4 * 20);
                return;
            } elseif ($block instanceof Sponge) {
                SpongeUtils::absorbWater($arena->getPlugin(), $pos, $block);
                return;
            } else {
                $arena->getBlockCollector()->addBlock($pos);
                return;
            }

            $event->cancel();
        }
    }
}