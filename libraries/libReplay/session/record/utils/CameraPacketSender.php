<?php

declare(strict_types=1);


namespace libReplay\session\record\utils;


use libReplay\session\record\tasks\UploadTask;
use NetherGames\NGEssentials\thread\NGThreadPool;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\Server;

class CameraPacketSender implements PacketSender
{
    /** @var ByteBufferWriter */
    private ByteBufferWriter $stream;
    /** @var Filmroll */
    private Filmroll $filmroll;
    /** @var bool */
    private bool $save = false;
    /** @var int */
    private int $startTick;

    /**
     * @param Server $server
     * @param int $replayId
     */
    public function __construct(private Server $server, private int $replayId)
    {
        $this->stream = new ByteBufferWriter();
        $this->startTick = $server->getTick();
    }

    public function setFilmroll(Filmroll $filmroll): void
    {
        $this->filmroll = $filmroll;
    }

    public function setSaving(bool $save): void
    {
        $this->save = $save;
    }

    public function send(string $payload, bool $immediate, ?int $receiptId): void
    {
        VarInt::writeUnsignedLong($this->stream, $this->server->getTick() - $this->startTick);
        CommonTypes::putString($this->stream, $payload);

        if ($receiptId !== null) {
            $this->filmroll->handleAckReceipt($receiptId);
        }
    }

    public function close(string $reason = "unknown reason"): void
    {
        if ($this->save) {
            $this->server->getLogger()->info("Saving replay $this->replayId");
            NGThreadPool::getInstance()->submitTask(new UploadTask($this->replayId, $this->stream->getData(), function (): void {
                MySQLCredentials::executeChange('replay.uploaded', ['replay_id' => $this->replayId]);
            }));
        }
    }
}