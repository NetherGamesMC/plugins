<?php

declare(strict_types=1);


namespace libReplay\session\record;


use libReplay\session\record\utils\Camera;
use libReplay\session\record\utils\RecordIdConverter;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\entity\object\Painting;
use pocketmine\world\World;

class Recording
{
    /** @var Camera */
    private Camera $camera;
    /** @var RecordIdConverter */
    private RecordIdConverter $converter;

    public function __construct(World $world, CameraFactory $cameraFactory, private int $replayId)
    {
        $this->converter = new RecordIdConverter();
        $this->camera = $camera = $cameraFactory->createCamera($this->converter, $world, $replayId);

        foreach ($world->getEntities() as $entity) {
            if (!$entity instanceof Painting) {
                $entity->spawnTo($camera);
            }
        }

        $ess = NGEssentials::getInstance();
        $entityManager = $ess->getEntityManager();
        $entityManager->spawnEntities([$camera], $entityManager->getEntities($world));

        foreach ($world->getLoadedChunks() as $chunkHash => $chunk) {
            World::getXZ($chunkHash, $x, $z);
            $world->registerChunkListener($camera, $x, $z);
        }
    }

    /**
     * @internal
     */
    public function stop(): void
    {
        $camera = $this->getCamera();

        if ($camera->isValid()) {
            $world = $camera->getWorld();

            foreach ($this->getConverter()->getMapping() as $externalId => $internalId) {
                if (($entity = $world->getEntity($externalId)) !== null) {
                    $entity->despawnFrom($camera);
                }
            }
        }

        $camera->getNetworkSession()->onClientDisconnect('');
    }

    /**
     * @return Camera
     */
    public function getCamera(): Camera
    {
        return $this->camera;
    }

    /**
     * @return RecordIdConverter
     */
    private function getConverter(): RecordIdConverter
    {
        return $this->converter;
    }

    public function getReplayId(): int
    {
        return $this->replayId;
    }
}