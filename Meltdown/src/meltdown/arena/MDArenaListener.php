<?php

namespace meltdown\arena;

use libminigames\ArenaListener;
use meltdown\utils\Items;
use NetherGames\NGEssentials\events\NGChatEvent;
use pocketmine\block\Block;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use function array_map;
use function in_array;

class MDArenaListener extends ArenaListener{

    public function onPlayerChat(NGChatEvent $event) : void{
        $player = $event->getPlayer();

        if($this->getArena()->isSpectator($player)){
            $event->setDisplayName(TextFormat::clean($player->getDisplayName()));
            $event->setRecipients($this->getArena()->getSpectators());
            $event->setPrefix('§7Dead Chat > ');
            $event->setStaffPrefix('§7Dead Chat Relay > ');
            $event->setSplitter(': ');
        }else{
            $event->setDisplayName($player->getDisplayName());
        }
    }

    public function getArena() : MDArena{
        /** @var MDArena $arena */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $arena = parent::getArena();

        return $arena;
    }

    public function onEntityDamage(EntityDamageEvent $event) : void{
        $player = $event->getEntity();

        if($player instanceof Player){
            $event->setBaseDamage(0);

            foreach($event->getModifiers() as $type => $amount){
                $event->setModifier($amount, $type);
            }

            if(($cause = $event->getCause()) === EntityDamageEvent::CAUSE_VOID){
                $event->cancel();
                $this->getArena()->onPlayerDeath($player);
            }else if($cause === EntityDamageEvent::CAUSE_FALL){
                $event->cancel();
            }
        }
    }

    public function onEntityExplode(EntityExplodeEvent $event) : void{
        $powerupHandler = $this->getArena()->getPowerupHandler();

        $blocks = $event->getBlockList();
        $arena = $this->getArena();
        $arenaFloors = $arena->getArenaConfig()->getAllFloors($arena);
        $blockHander = $arena->getBlockHandler();

        $entity = $event->getEntity();
        if($entity instanceof Player){
            $player = $entity;
        }elseif($entity instanceof Projectile && ($owningEntity = $entity->getOwningEntity()) instanceof Player){
            $player = $owningEntity;
        }else{
            $player = null;
        }

        foreach($blocks as $hash => $block){
            $position = $block->getPosition();
            $floor = $position->getFloorY();

            if(in_array($floor, $arenaFloors, true)){
                $powerupHandler->removeBlock($block->getPosition()->asVector3());
            }else{
                unset($blocks[$hash]);
            }
        }

        if($player !== null){
            $blockHander->addToRecentlyDeletedToPlayerMap($player, ...array_map(fn(Block $block) => $block->getPosition()->asVector3(), $blocks));
        }

        $event->setBlockList($blocks);
    }

    public function onPlayerMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $arena = $this->getArena();
        $blockHandler = $arena->getBlockHandler();

        if($blockHandler->processRequests && $arena->isInArena($player) && !$arena->isSpectator($player)){
            $from = $event->getFrom();

            $blockHandler->handleNewPosition($player, $from);
            $blockHandler->handleBlockSteppedOn($from->floor()->down(), $player);
        }
    }

    public function onEntityEffectAdd(EntityEffectAddEvent $event) : void{
        $player = $event->getEntity();

        if($player instanceof Player){
            $effect = $event->getEffect();
            $armorInventory = $player->getArmorInventory();

            if($effect->getType() === VanillaEffects::SPEED()){
                $armorInventory->setBoots(Items::getSlipperyBoots());
            }elseif($effect->getType() === VanillaEffects::WATER_BREATHING()){
                $armorInventory->setBoots(Items::getIceBoots());
            }
        }
    }

    public function onEntityEffectRemove(EntityEffectRemoveEvent $event) : void{
        $player = $event->getEntity();

        if($player instanceof Player){
            $effect = $event->getEffect();
            $armorInventory = $player->getArmorInventory();

            if(($effect->getType() === VanillaEffects::SPEED() && $armorInventory->getBoots()->equals(Items::getSlipperyBoots()))
                || ($effect->getType() === VanillaEffects::WATER_BREATHING() && $armorInventory->getBoots()->equals(Items::getIceBoots()))
            ){
                $player->getArmorInventory()->setBoots(VanillaItems::AIR());
            }
        }
    }

    private function explosion(Entity $entity, Position $location, float $radius = 3) : void{
        $explosion = new Explosion($location, $radius, $entity);
        $explosion->explodeA();
        $explosion->explodeB();
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event) : void{
        if($event->getEntity() instanceof Snowball){
            $this->explosion($event->getEntity(), $event->getBlockHit()->getPosition(), 3);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $event->cancel();
    }
}