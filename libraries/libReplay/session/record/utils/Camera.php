<?php

declare(strict_types=1);


namespace libReplay\session\record\utils;


use pocketmine\entity\Location;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\world\ChunkListenerNoOpTrait;
use pocketmine\world\format\Chunk;

class Camera extends Player
{
    public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, Location $spawnLocation)
    {
        parent::__construct($server, $session, $playerInfo, true, $spawnLocation, null);

        $world = $spawnLocation->getWorld();
        $world->unregisterChunkLoader($this->chunkLoader, $spawnLocation->getFloorX() >> 4, $spawnLocation->getFloorZ() >> 4);
        $this->usedChunks = [];

        $spawnLocation->getWorld()->removeEntity($this);
    }

    public function sendSpawnPacket(Player $player): void
    {

    }

    use ChunkListenerNoOpTrait {
        onChunkUnloaded as private;
    }

    public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk): void
    {
        $this->getWorld()->unregisterChunkListener($this, $chunkX, $chunkZ);
    }

    public function getFilmroll(): Filmroll
    {
        /** @var Filmroll $filmroll */
        $filmroll = $this->getNetworkSession();

        return $filmroll;
    }

    public function hasReceivedChunk(int $chunkX, int $chunkZ): bool
    {
        return true;
    }
}