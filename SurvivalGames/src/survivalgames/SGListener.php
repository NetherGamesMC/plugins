<?php

declare(strict_types=1);

namespace survivalgames;

use libminigames\MinigameListener;
use libVanilla\entity\object\FishingHook;
use libVanilla\event\fishing\FishingRodRetractionEvent;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use survivalgames\utils\TranslationKeys;
use function spl_object_hash;

class SGListener extends MinigameListener
{
    /** @var int[][] */
    private array $cooldown = [];

    /**
     * Returns if an item is on cooldown based on seconds passed to var $cooldown
     *
     * @param Player $player target item's owner
     * @param Item $item item to check cooldown for
     * @param int $cooldown in seconds
     *
     * @return bool
     */
    private function checkCooldown(Player $player, Item $item, int $cooldown = 5): bool
    {
        $playerHash = spl_object_hash($player);
        $itemHash = spl_object_hash($item);

        if (!isset($this->cooldown[$playerHash][$itemHash])) {
            $this->cooldown[$playerHash][$itemHash] = time() + $cooldown;
            return false;
        }

        if (time() >= $this->cooldown[$playerHash][$itemHash]) {
            $this->cooldown[$playerHash][$itemHash] = time() + $cooldown;
            return false;
        }
        return true;
    }

    /**
     * returns time left in seconds until item's cooldown is over
     *
     * @param Player $player target item's owner
     * @param Item $item item to check cooldown for
     *
     * @return int
     */
    private function getCooldown(Player $player, Item $item): int
    {
        return $this->cooldown[spl_object_hash($player)][spl_object_hash($item)] - time();
    }

    /**
     * This function uses the player's own motion to direct the player towards
     * the grappling rod hook, giving sort of a "push" towards the hook to the player
     *
     * @param Player $player
     * @param FishingHook $hook
     */
    private function doGrapplingRodMotion(Player $player, FishingHook $hook): void
    {
        $deltaX = -($player->getPosition()->getX() - $hook->getPosition()->getX());
        $deltaZ = -($player->getPosition()->getZ() - $hook->getPosition()->getZ());
        $deltaY = $hook->getPosition()->getY() - $player->getPosition()->getY();

        $base = 2;
        $force = sqrt($deltaX * $deltaX + $deltaZ * $deltaZ);

        if ($force <= 0) {
            return;
        }

        $force = 1 / $force;
        $motion = clone $player->getMotion();

        $motion->x /= 2;
        $motion->z /= 2;
        $motion->x += $deltaX * $force * $base;
        $motion->z += $deltaZ * $force * $base;

        $motion->y = 0.75 + ($deltaY * 0.05);

        if ($motion->y > $base) {
            $motion->y = $base;
        }

        $player->setMotion($motion);
    }

    /**
     * @priority MONITOR
     */
    public function onRodRetraction(FishingRodRetractionEvent $event): void
    {
        $rod = $event->getRod();
        $player = $event->getPlayer();
        $hook = $rod->getFishingHook($player);

        $isGrapplingRod = $rod->getNamedTag()->getByte("grappling_rod", 0) === 1;

        if ($hook !== null && $isGrapplingRod && $player->isOnGround() && $hook->isOnGround()) {
            if ($this->checkCooldown($player, $rod)) {
                $cooldown = $this->getCooldown($player, $rod);
                Translator::sendMessage($player, TranslationKeys::SKYWARS_COOLDOWN_GRAPPLING_ROD, Translator::TYPE_WARNING, ...["seconds" => (string)$cooldown]);
                return;
            }

            $this->doGrapplingRodMotion($player, $hook);
            $rod->setDamage(0);
        }
    }
}