<?php
/**
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

namespace conquests\utils\world;

use conquests\CQArena;
use conquests\utils\entity\PrimedTNT;
use libVanilla\entity\object\Fireball;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\TNT;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Limits;
use pocketmine\world\format\SubChunk;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use function min;
use function mt_rand;
use function random_int;
use function sqrt;

class Explosion extends \pocketmine\world\Explosion
{
    private int $rays = 16;
    private SubChunkExplorer $subChunkExplorer;

    public function __construct(Position $center, float $size, private CQArena $arena, protected Entity|Block|null $what = null, private bool $showParticle = true, private bool $setOnFire = false, private float $maxDamage = Limits::INT32_MAX)
    {
        parent::__construct($center, $size, $what);
        $this->subChunkExplorer = new SubChunkExplorer($this->world);
        if ($this->what instanceof Fireball) {
            $this->radius = 3.0;
            $this->maxDamage = 1.75;
        }
    }

    /**
     * Calculates which blocks will be destroyed by this explosion. If explodeB() is called without calling this, no blocks
     * will be destroyed.
     */
    public function explodeA(): bool
    {
        if ($this->radius < 0.1) {
            return false;
        }

        $blockFactory = RuntimeBlockStateRegistry::getInstance();
        $hasNoProtection = $this->arena->getGameSettings()->hasNoProtection();

        $mRays = $this->rays - 1;
        for ($i = 0; $i < $this->rays; ++$i) {
            for ($j = 0; $j < $this->rays; ++$j) {
                for ($k = 0; $k < $this->rays; ++$k) {
                    if ($i === 0 || $i === $mRays || $j === 0 || $j === $mRays || $k === 0 || $k === $mRays) {
                        //this could be written as new Vector3(...)->normalize()->multiply(stepLen), but we're avoiding Vector3 for performance here
                        [$shiftX, $shiftY, $shiftZ] = [$i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1];
                        $len = sqrt($shiftX ** 2 + $shiftY ** 2 + $shiftZ ** 2);
                        [$shiftX, $shiftY, $shiftZ] = [($shiftX / $len) * $this->stepLen, ($shiftY / $len) * $this->stepLen, ($shiftZ / $len) * $this->stepLen];
                        $pointerX = $this->source->x;
                        $pointerY = $this->source->y;
                        $pointerZ = $this->source->z;

                        for ($blastForce = $this->radius * (random_int(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75) {
                            $x = (int)$pointerX;
                            $y = (int)$pointerY;
                            $z = (int)$pointerZ;
                            $vBlockX = $pointerX >= $x ? $x : $x - 1;
                            $vBlockY = $pointerY >= $y ? $y : $y - 1;
                            $vBlockZ = $pointerZ >= $z ? $z : $z - 1;

                            $pointerX += $shiftX;
                            $pointerY += $shiftY;
                            $pointerZ += $shiftZ;

                            if ($this->subChunkExplorer->moveTo($vBlockX, $vBlockY, $vBlockZ) === SubChunkExplorerStatus::INVALID) {
                                continue;
                            }
                            $subChunk = $this->subChunkExplorer->currentSubChunk;
                            if ($subChunk === null) {
                                throw new AssumptionFailedError("SubChunkExplorer subchunk should not be null here");
                            }

                            $state = $subChunk->getBlockStateId($vBlockX & SubChunk::COORD_MASK, $vBlockY & SubChunk::COORD_MASK, $vBlockZ & SubChunk::COORD_MASK);
                            $type = $state >> Block::INTERNAL_STATE_DATA_BITS;
                            $isProtected = true;
                            for ($dx = -1; $dx <= 1 && $isProtected; ++$dx) {
                                for ($dy = -1; $dy <= 1 && $isProtected; ++$dy) {
                                    for ($dz = -1; $dz <= 1 && $isProtected; ++$dz) {
                                        if ($dx === 0 && $dy === 0 && $dz === 0) {
                                            continue;
                                        }

                                        $surroundingX = $vBlockX + $dx;
                                        $surroundingY = $vBlockY + $dy;
                                        $surroundingZ = $vBlockZ + $dz;
                                        if ($this->subChunkExplorer->moveTo($surroundingX, $surroundingY, $surroundingZ) === SubChunkExplorerStatus::INVALID) {
                                            $isProtected = false;
                                            continue;
                                        }

                                        $surroundingSubChunk = $this->subChunkExplorer->currentSubChunk;
                                        if ($surroundingSubChunk === null) {
                                            $isProtected = false;
                                            continue;
                                        }

                                        $surroundingState = $surroundingSubChunk->getBlockStateId($surroundingX & SubChunk::COORD_MASK, $surroundingY & SubChunk::COORD_MASK, $surroundingZ & SubChunk::COORD_MASK);
                                        $isProtected = ($surroundingState >> Block::INTERNAL_STATE_DATA_BITS) === BlockTypeIds::GLASS || $blockFactory->blastResistance[$surroundingState] > $blockFactory->blastResistance[$state];
                                    }
                                }
                            }

                            $blastResistance = match (true) {
                                ($type === BlockTypeIds::STAINED_GLASS || $isProtected) && !$hasNoProtection => BlockBreakInfo::indestructible()->getBlastResistance(),
                                $type === BlockTypeIds::END_STONE && $this->what instanceof Fireball => BlockBreakInfo::indestructible()->getBlastResistance(),
                                default => $blockFactory->blastResistance[$state]
                            };
                            if ($blastResistance >= 0) {
                                $blastForce -= ($blastResistance / 5 + 0.3) * $this->stepLen;
                                if ($blastForce > 0) {
                                    if (!isset($this->affectedBlocks[World::blockHash($vBlockX, $vBlockY, $vBlockZ)])) {
                                        $_block = $this->world->getBlockAt($vBlockX, $vBlockY, $vBlockZ, true, false);
                                        if ($this->arena->getBlockCollector()->isBreakable($_block->getPosition())) {
                                            foreach ($_block->getAffectedBlocks() as $_affectedBlock) {
                                                $_affectedBlockPos = $_affectedBlock->getPosition();
                                                $this->affectedBlocks[World::blockHash($_affectedBlockPos->getFloorX(), $_affectedBlockPos->getFloorY(), $_affectedBlockPos->getFloorZ())] = $_affectedBlock;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function explodeB(): bool
    {
        $owner = $this->what instanceof Entity ? $this->what->getOwningEntity() : null;
        $team = $owner instanceof Player && $this->arena->isInArena($owner) ? $this->arena->getTeam($owner) : null;
        $source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $yield = min(100, (1 / $this->radius) * 100);

        if ($this->what instanceof Entity) {
            $ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield, []);
            $ev->call();

            if ($ev->isCancelled()) {
                return false;
            }

            $yield = $ev->getYield();
            $this->affectedBlocks = $ev->getBlockList();
        }

        $explosionSize = $this->radius * 2;
        $minX = (int)floor($this->source->x - $explosionSize - 1);
        $maxX = (int)ceil($this->source->x + $explosionSize + 1);
        $minY = (int)floor($this->source->y - $explosionSize - 1);
        $maxY = (int)ceil($this->source->y + $explosionSize + 1);
        $minZ = (int)floor($this->source->z - $explosionSize - 1);
        $maxZ = (int)ceil($this->source->z + $explosionSize + 1);

        $explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $list = $this->world->getNearbyEntities($explosionBB, ($this->what instanceof Entity && !$this->what instanceof Player) ? $this->what : null);
        foreach ($list as $entity) {
            if ($entity instanceof Fireball) {
                $entity->flagForDespawn();
            } elseif ($entity instanceof ItemEntity || ($entity instanceof Player && $entity->isSpectator())) {
                continue;
            }

            $entityPos = $entity->getPosition();
            $distance = $entityPos->distance($this->source) / $explosionSize;

            if ($distance <= 1) {
                if ($this->setOnFire) {
                    $entity->setOnFire(15);
                }

                $motion = $entityPos->subtractVector($this->source)->normalize();
                $exposure = $this->getExposure($this->source, $entity);

                $impact = (1 - $distance) * $exposure;

                $damage = min((int)(((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1) / 4), 6);

                if ($damage > $this->maxDamage) {
                    $damage = $this->maxDamage;
                }

                if ($this->what instanceof Entity) {
                    $ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
                } elseif ($this->what instanceof Block) {
                    $ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                } else {
                    $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }

                $entity->attack($ev);

                $motion->x *= 1.2;
                $motion->y *= 1.3;
                $motion->z *= 1.2;

                $victimTeam = $entity instanceof Player && $this->arena->isInArena($entity) ? $this->arena->getTeam($entity) : null;
                $isPlacer = $victimTeam === $team;
                if ($this->what instanceof PrimedTNT && $entity instanceof Player && $entity->isSneaking()) {
                    $motion->x *= $isPlacer ? 0.50 : 0.60;
                    $motion->y *= $isPlacer ? 0.30 : 0.40;
                    $motion->z *= $isPlacer ? 0.50 : 0.60;
                } else if ($this->what instanceof Fireball && $entity instanceof Player) {
                    $resistance = $entity->isSneaking() || $entity->getEffects()->has(VanillaEffects::LEVITATION());
                    if (!$isPlacer) {
                        $motion->x *= $resistance ? 0.50 : 0.80;
                        $motion->y *= $resistance ? 0.50 : 0.70;
                        $motion->z *= $resistance ? 0.50 : 0.80;
                    } else if ($resistance) {
                        $motion->x *= 0.40;
                        $motion->y *= 0.40;
                        $motion->z *= 0.40;
                    }
                }

                $entity->setMotion($entity->getMotion()->addVector($motion->multiply($impact ** 0.75)));
            }
        }

        $air = VanillaItems::AIR();
        $airBlock = VanillaBlocks::AIR();

        foreach ($this->affectedBlocks as $block) {
            $pos = $block->getPosition();
            if ($block instanceof TNT) {
                $block->ignite(random_int(10, 30));
            } else {
                if (mt_rand(0, 100) < $yield) {
                    foreach ($block->getDrops($air) as $drop) {
                        $this->world->dropItem($pos->add(0.5, 0.5, 0.5), $drop);
                    }
                }

                if (($t = $this->world->getTileAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ())) !== null) {
                    $t->onBlockDestroyed(); //needed to create drops for inventories
                }
                $this->world->setBlockAt($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $airBlock);
                $this->world->updateAllLight($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());
            }
        }

        if ($this->showParticle) {
            $this->world->addParticle($source, new HugeExplodeSeedParticle());
        }

        $this->world->addSound($source, new ExplodeSound());

        return true;
    }
}
