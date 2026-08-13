<?php

declare(strict_types=1);


namespace libReplay\session\record\tasks;


use GlobalLogger;
use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use libasyncio\s3\task\S3UploadObjectTask;
use libReplay\S3Provider;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use function is_callable;
use function zstd_compress;
use function zstd_uncompress;
use const ZSTD_COMPRESS_LEVEL_MAX;

class UploadTask extends AsyncTask
{
    private const CALLABLE = 'closure';
    public const RETRY_UPLOAD_PERIOD = 2;

    /** @phpstan-var NonThreadSafeValue<S3StorageCredentials> */
    private NonThreadSafeValue $credentials;

    public function __construct(private int $replayId, private string $payload, callable $callable)
    {
        if (($credentials = S3Provider::getStorageCredentials()) === null) {
            throw new \RuntimeException("S3 credentials are not set.");
        }

        $this->credentials = new NonThreadSafeValue($credentials);
        $this->storeLocal(self::CALLABLE, $callable);
    }

    public function onRun(): void
    {
        $this->upload($this->credentials->deserialize(), $this->compress());
    }

    public function upload(S3StorageCredentials $credentials, string $payload, int $attempts = 10): void
    {
        try {
            S3UploadObjectTask::uploadObject($credentials, new S3ObjectIdentifier($this->replayId . ".zstd"), $payload);
        } catch (S3StorageError) {
            if ($attempts > 0) {
                GlobalLogger::get()->warning("Failed to upload object into S3, retrying...");
                $this->synchronized(function (): void {
                    $this->wait((int)floor(self::RETRY_UPLOAD_PERIOD * 1000000));
                });

                $this->upload($credentials, $payload, --$attempts);
            } else {
                GlobalLogger::get()->error("S3 object upload failed.");
            }
        }
    }

    /**
     * @throws DataDecodeException
     */
    public function compress(): string
    {
        $oldSerializer = new ByteBufferReader($this->payload);
        $newSerializer = new ByteBufferWriter();
        unset($this->payload);

        while ($oldSerializer->getUnreadLength() > 0) {
            VarInt::writeUnsignedLong($newSerializer, VarInt::readUnsignedLong($oldSerializer));

            $compressedData = CommonTypes::getString($oldSerializer);

            /** @var string $data */
            $data = zstd_uncompress(substr($compressedData, 1)); // remove the compression method byte

            CommonTypes::putString($newSerializer, $data);
        }

        unset($oldSerializer);

        /** @var string $result */
        $result = zstd_compress($newSerializer->getData(), ZSTD_COMPRESS_LEVEL_MAX);

        unset($newSerializer);

        return $result;
    }

    public function onCompletion(): void
    {
        $callable = $this->fetchLocal(self::CALLABLE);

        if (is_callable($callable)) {
            $callable($this->getResult());
        }
    }
}