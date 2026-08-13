<?php

namespace NetherGames\NGEssentials\utils;

use NetherGames\NGEssentials\NGEssentials;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\BlockParticle;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\particle\ItemParticle;
use pocketmine\world\particle\Particle;
use pocketmine\world\particle\ProtocolParticle;
use pocketmine\world\World;

class ParticleOptimizer extends BaseClass
{
    use SingletonTrait;

    /** @var array */
    private array $particles = [];

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct($plugin);

        $this->getPlugin()->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function () {
            $this->sendParticles();
        }), 1, 1);
    }

    public function sendParticles(): void
    {
        $plugin = $this->getPlugin();
        $server = $plugin->getServer();
        $playerManager = $plugin->getPlayerManager();

        foreach ($this->particles as $worldId => $particleData) {
            $world = $server->getWorldManager()->getWorld($worldId);

            if ($world === null) {
                continue;
            }

            foreach ($particleData as $chunkHash => $entries) {
                World::getXZ($chunkHash, $chunkX, $chunkZ);
                $viewers = $playerManager->unsetFPSPlayers($world->getChunkPlayers($chunkX, $chunkZ));

                TypeConverter::broadcastByTypeConverter($viewers, function (TypeConverter $typeConverter) use ($entries): array {
                    $packets = [];

                    foreach ($entries as [$particle, $pos]) {
                        if ($particle instanceof BlockParticle) {
                            $particle->setBlockTranslator($typeConverter->getBlockTranslator());
                        } else if ($particle instanceof ItemParticle) {
                            $particle->setItemTranslator($typeConverter->getItemTranslator());
                        } else if ($particle instanceof ProtocolParticle) {
                            $particle->setProtocolId($typeConverter->getProtocolId());
                        }

                        if ($particle instanceof HugeExplodeSeedParticle) {
                            $packets[] = LevelSoundEventPacket::nonActorSound(LevelSoundEvent::EXPLODE, $pos, false);
                        }

                        foreach ($particle->encode($pos) as $pk) {
                            $packets[] = $pk;
                        }
                    }

                    return $packets;
                });
            }
        }

        $this->particles = [];
    }

    /**
     * @param Particle $particle
     * @param Vector3 $pos
     * @param World $world
     */
    public function addParticle(Particle $particle, Vector3 $pos, World $world): void
    {
        $chunkX = $pos->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $pos->getFloorZ() >> Chunk::COORD_BIT_SIZE;
        $worldId = $world->getId();

        $this->particles[$worldId][World::chunkHash($chunkX, $chunkZ)][] = [$particle, $pos];
    }
}