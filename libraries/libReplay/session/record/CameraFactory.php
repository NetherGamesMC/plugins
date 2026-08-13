<?php

declare(strict_types=1);


namespace libReplay\session\record;


use libReplay\protocol\BlockChangePacket;
use libReplay\session\record\utils\Camera;
use libReplay\session\record\utils\CameraPacketBroadcaster;
use libReplay\session\record\utils\CameraPacketSender;
use libReplay\session\record\utils\Filmroll;
use libReplay\session\record\utils\RecordIdConverter;
use libReplay\session\utils\ZstdCompressor;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\NetworkSessionManager;
use pocketmine\player\PlayerInfo;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;
use function str_repeat;

class CameraFactory
{
    /** @var int */
    private int $id = 1;
    /** @var PacketPool */
    private PacketPool $packetPool;
    /** @var ZstdCompressor */
    private ZstdCompressor $compressor;
    /** @var PacketBroadcaster */
    private PacketBroadcaster $packetBroadcaster;
    /** @var Skin */
    private Skin $skin;

    public function __construct(private NetworkSessionManager $sessionManager)
    {
        $packetPool = PacketPool::getInstance();
        $packetPool->registerPacket(new BlockChangePacket());
        $this->packetPool = $packetPool;

        $this->packetBroadcaster = new CameraPacketBroadcaster();

        $this->compressor = ZstdCompressor::getInstance();
        $this->skin = new Skin('Standard_Custom', str_repeat("\x00", 64 * 64 * 4));
    }

    public function createCamera(RecordIdConverter $converter, World $world, int $replayId): Camera
    {
        $typeConverter = TypeConverter::getInstance();
        $server = $world->getServer();

        $filmroll = new Filmroll(
            $server,
            $this->sessionManager,
            $this->packetPool,
            $sender = new CameraPacketSender($server, $replayId),
            $this->packetBroadcaster,
            $server->getEntityEventBroadcaster($this->packetBroadcaster, $typeConverter),
            $this->compressor,
            $typeConverter,
            $converter,
            $playerInfo = new PlayerInfo((string)$this->id++, Uuid::uuid4(), $this->skin, 'en_US')
        );
        $camera = new Camera($server, $filmroll, $playerInfo, Location::fromObject($world->getSpawnLocation(), $world));
        $filmroll->setCamera($camera);
        $sender->setFilmroll($filmroll);

        return $camera;
    }
}