<?php

namespace meltdown\arena\handler;

use meltdown\arena\MDArena;
use meltdown\utils\math\PlayerTrajectory;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\Position;
use function time;

class BlockHandler{

    /** @var int How long do we wait until we remove a block from under a player's feet? (in ticks) */
    public const BLOCK_REMOVAL_DELAY = 15;

    /** @var int How long after you break a block is a kill still counted? This should be > BLOCK_REMOVAL_DELAY */
    public const DELAY_TO_KILL = 4 * 20;
    /** @var bool Cooldown for break the blocks */
    public bool $processRequests = false;
    /** @var MDArena */
    private MDArena $arena;
    /** @var array<string, Vector3> [vec3->__toString() => vec3] Bit ugly but it's for performance purposes */
    private array $toBeDeletedQueue = [];
    /** @var array<string, Player> [vec3->__toString() => Player that deleted it] */
    private array $recentlyDeletedToPlayerMap = [];
    /** @var array<string, PlayerTrajectory> */
    private array $trajectories = [];
    /** @var array<string, array{0: Vector3, 1: float}> */
    private array $lastMoves = [];

    /**
     * @param MDArena $arena
     */
    public function __construct(MDArena $arena){
        $this->arena = $arena;
    }

    public function checkLastMovements() : void{
        $now = microtime(true);
        foreach($this->getArena()->getAlivePlayers() as $player){
            if(isset($this->lastMoves[$player->getName()])){
                $lastMove = $this->lastMoves[$player->getName()][1];
                if(($now - $lastMove) > 1.5){ //1.5 seconds
                    unset($this->lastMoves[$player->getName()]);
                    $this->handleNewPosition($player, $player->getPosition());

                    foreach($this->getArena()->getWorld()->getCollisionBlocks($player->getBoundingBox()->offsetCopy(0, -0.5, 0)) as $block){
                        $this->handleBlockSteppedOn($block->getPosition()->asVector3(), $player);
                    }
                }
            }
        }
    }

    /**
     * @return MDArena
     */
    public function getArena() : MDArena{
        return $this->arena;
    }

    /**
     * @param Player        $player
     * @param Position|null $from
     */
    public function handleNewPosition(Player $player, ?Position $from = null) : void{
        if(!isset($this->lastMoves[$player->getName()])){
            $this->lastMoves[$player->getName()] = [$player->getPosition()->asVector3(), microtime(true)];
        }else{
            $lastPos = $this->lastMoves[$player->getName()][0];
            if($from->distance($lastPos) > 1){
                $this->lastMoves[$player->getName()] = [$player->getPosition()->asVector3(), microtime(true)];
            }
        }

        if(isset($this->trajectories[$player->getName()])){
            $trajectory = $this->trajectories[$player->getName()];
            if($from instanceof Position && $from->asVector3()->equals($trajectory->getLastPosition())){
                return;
            }

            if($player->isOnGround()){
                if($trajectory->isFall()){
                    $halfWidth = $player->size->getWidth() / 2;
                    foreach($trajectory->getCollisionPositions($halfWidth, $halfWidth) as $position){
                        if(isset($this->recentlyDeletedToPlayerMap[(string) $position])){
                            $killer = $this->recentlyDeletedToPlayerMap[(string) $position];
                            if($killer !== $player){
                                $this->getArena()->addKill($killer, $player);
                                break;
                            }
                        }
                    }

                    $combatLogger = $this->arena->getPlugin()->getEssentials()->getCombatLogger();
                    if(($lastHit = $combatLogger->getLog($player)->getLatestHit()) !== null && $lastHit->getTime() + 5 > time() && ($damager = $player->getServer()->getPlayerExact($lastHit->getDamagerName())) !== null && $this->getArena()->isInArena($damager)){
                        $this->getArena()->addKill($damager, $player);
                    }
                }

                unset($this->trajectories[$player->getName()]);
            }else{
                $trajectory->addPosition($player->getPosition(), $player->getInAirTicks() - $trajectory->getTotalTicks());
            }
        }else{
            if($player->isOnGround()){
                $this->trajectories[$player->getName()] = new PlayerTrajectory($player->getPosition());
            }
        }

        if($player->getPosition()->getFloorY() < $this->getArena()->getArenaConfig()->getMinFloor($this->getArena()) - 5){
            if(isset($this->trajectories[$player->getName()])){
                $trajectory = $this->trajectories[$player->getName()];
                $halfWidth = $player->size->getWidth() / 2;
                foreach($trajectory->getCollisionPositions($halfWidth, $halfWidth) as $position){
                    if(isset($this->recentlyDeletedToPlayerMap[(string) $position])){
                        $killer = $this->recentlyDeletedToPlayerMap[(string) $position];
                        if($killer !== $player){
                            $this->getArena()->addKill($killer, $player);
                            break;
                        }
                    }
                }
                unset($this->trajectories[$player->getName()]);
            }
            $this->getArena()->onPlayerDeath($player);
        }
    }

    /**
     * @return Vector3[]
     */
    private function getBlocksInHorizontalRadius(Vector3 $vector3, int $radius) : array{
        $blocks = [];
        for($x = -$radius; $x <= $radius; ++$x){
            for($z = -$radius; $z <= $radius; ++$z){
                $blocks[] = $vector3->add($x, 0, $z);
            }
        }

        return $blocks;
    }

    public function handleBlockSteppedOn(Vector3 $blockVec3, Player $player) : void{
        if($player->getEffects()->has(VanillaEffects::WATER_BREATHING())){
            return;
        }

        $arenaConfig = $this->getArena()->getArenaConfig();
        if($blockVec3->getFloorY() < $arenaConfig->getMinFloor($this->getArena())
            || $blockVec3->getFloorY() > $arenaConfig->getMaxFloor($this->getArena())){
            return;
        }

        foreach($this->getBlocksInHorizontalRadius($blockVec3, 1) as $vector3){
            if(isset($this->toBeDeletedQueue[$blockStr = (string) $vector3])){
                return;
            }

            $this->toBeDeletedQueue[$blockStr] = $vector3;
            $this->queueForDeletion($player, Position::fromObject($vector3, $player->getWorld()));
        }
    }

    public function addToRecentlyDeletedToPlayerMap(Player $player, Vector3 ...$positions) : void{
        foreach($positions as $position){
            $this->recentlyDeletedToPlayerMap[(string) $position->asVector3()] = $player;
        }

        $this->getArena()->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($positions) : void{
            foreach($positions as $position){
                unset($this->recentlyDeletedToPlayerMap[(string) $position->asVector3()]);
            }
        }), self::DELAY_TO_KILL);
    }

    private function queueForDeletion(Player $player, Position $position) : void{
        try{
            $world = $position->getWorld();
            $block = $world->getBlock($position);

            if(!$this->getArena()->isRunning() || $block->getTypeId() === BlockTypeIds::AIR){
                return;
            }
            $nextBlock = $this->getNextBlock($block);

            if($nextBlock->getTypeId() === BlockTypeIds::AIR){
                $this->deleteBlock($block);

                unset($this->toBeDeletedQueue[(string) $position->asVector3()]);
                $this->addToRecentlyDeletedToPlayerMap($player, $position);
            }else{
                $world->setBlock($block->getPosition(), $nextBlock);
                $this->getArena()->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $position) : void{
                    $this->queueForDeletion($player, $position);
                }), self::BLOCK_REMOVAL_DELAY);
            }
        }catch(AssumptionFailedError){
            //do nothing
        }
    }

    /**
     * @param Block $block
     *
     * @return Block
     */
    public function getNextBlock(Block $block) : Block{
        if(($typeId = $block->getTypeId()) === BlockTypeIds::SNOW){
            return VanillaBlocks::BLUE_ICE();
        }elseif($typeId === BlockTypeIds::BLUE_ICE){
            return VanillaBlocks::PACKED_ICE();
        }elseif($typeId === BlockTypeIds::PACKED_ICE){
            return VanillaBlocks::ICE();
        }

        return VanillaBlocks::AIR();
    }

    /**
     * @param Block $block
     */
    public function deleteBlock(Block $block) : void{
        $this->getArena()->getWorld()->setBlock($block->getPosition(), VanillaBlocks::AIR());
        $this->getArena()->getPowerupHandler()->removeBlock($block->getPosition()->asVector3());
    }
}