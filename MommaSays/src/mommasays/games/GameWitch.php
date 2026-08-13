<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */
declare(strict_types=1);

namespace mommasays\games;

use mommasays\games\traits\BlockPlaceTrait;
use mommasays\utils\entity\FloatingItem;
use mommasays\utils\entity\WitchGameEntity;
use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\{EntityDamageByEntityEvent, EntityDamageEvent};
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_map;
use function array_rand;
use function in_array;

class GameWitch extends Game
{
    use BlockPlaceTrait;

    /** @var Item */
    private Item $targetFlower;

    public function getMessage(): string
    {
        return 'Give Grandma a ' . $this->targetFlower->getName();
    }

    public function setupArena(): void
    {
        $world = $this->getArena()->getWorld();

        $this->selectRandomFlower();
        $this->setWitchGameBlocks($world);

        $entity = new WitchGameEntity(Location::fromObject(new Vector3(13.5, 51, 0.5), $world));
        $entity->setNameTag(TextFormat::YELLOW . 'Grandma Witch' . TextFormat::EOL . TextFormat::GRAY . '(Left Click)');
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
        $entity->spawnToAll();

        $itemEntity = FloatingItem::getFrom(Location::fromObject(new Vector3(12.5, 53, 0.5), $world), $this->targetFlower);
        $itemEntity->setNameTag("§6§lThe Witch Needs: \n" . $this->targetFlower->getName());
        $itemEntity->setNameTagAlwaysVisible();
        $itemEntity->setNameTagVisible();
        $itemEntity->spawnToAll();

        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->setGamemode(GameMode::SURVIVAL);
        }
    }

    private function selectRandomFlower(): void
    {
        $flowers = $this->getFlowers();

        $flowerBlock = $flowers[array_rand($flowers)];
        $this->targetFlower = $flowerBlock->asItem();
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if (!in_array($event->getBlock()->getStateId(), array_map(static function (Block $block) {
            return $block->getStateId();
        }, $this->getFlowers()), true)) {
            $event->cancel();
        }
    }

    public function resetArena(): void
    {
        $world = $this->getArena()->getWorld();

        foreach ($world->getEntities() as $entity) {
            if ($entity instanceof WitchGameEntity || $entity instanceof ItemEntity) {
                $entity->flagForDespawn();
            }
        }
        foreach ($this->getArena()->getAlivePlayers() as $player) {
            $player->setGamemode(GameMode::ADVENTURE);
            $player->getInventory()->clearAll();
        }

        $this->resetWitchGameBlocks($world);
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($event instanceof EntityDamageByEntityEvent && $entity instanceof WitchGameEntity) {
            $damager = $event->getDamager();
            if (($damager instanceof Player) && !$this->isWinner($damager->getName()) && $damager->getInventory()->getItemInHand()->equals($this->targetFlower)) {
                $this->addWinner($damager);
            }
            $event->cancel();
        }
    }
}
