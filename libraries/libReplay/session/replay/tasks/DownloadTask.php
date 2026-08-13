<?php

declare(strict_types=1);


namespace libReplay\session\replay\tasks;

use GlobalLogger;
use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use libasyncio\s3\task\S3GetObjectTask;
use libReplay\S3Provider;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Utils;
use RuntimeException;
use function is_callable;
use function zstd_uncompress;

class DownloadTask extends AsyncTask
{
    private const CALLABLE = 'closure';
    private const RETRY_DOWNLOAD_PERIOD = 2;

    /** @var string */
    private string $credentials;

    public function __construct(private int $replayId, callable $callable)
    {
        Utils::validateCallableSignature(function (string $result): void {}, $callable);

        $serialized = igbinary_serialize(S3Provider::getStorageCredentials());
        if (!is_string($serialized)) {
            throw new RuntimeException("Serialized S3StorageCredentials must always be string");
        }

        $this->credentials = $serialized;

        $this->storeLocal(self::CALLABLE, $callable);
    }

    public function onRun(): void
    {
        /** @var S3StorageCredentials $credentials */
        $credentials = igbinary_unserialize($this->credentials);

        $compressedPayload = $this->tryAndGetPayload($credentials, $this->replayId);

        if ($compressedPayload === null) {
            $this->setResult("");
        } else {
            $this->setResult(zstd_uncompress($compressedPayload));
        }
    }

    private function tryAndGetPayload(S3StorageCredentials $credentials, int $replayId, int $attempts = 10): ?string
    {
        try {
            return S3GetObjectTask::getObject($credentials, new S3ObjectIdentifier($this->replayId . ".zstd"));
        } catch (S3StorageError) {
            if ($attempts > 0) {
                GlobalLogger::get()->warning("Failed to download replay $replayId, retrying...");
                $this->synchronized(function (): void {
                    $this->wait((int)floor(self::RETRY_DOWNLOAD_PERIOD * 1000000));
                });

                return $this->tryAndGetPayload($credentials, $replayId, --$attempts);
            } else {
                GlobalLogger::get()->error("S3 object download failed.");
            }
        }

        return null;
    }

    public function onCompletion(): void
    {
        $callable = $this->fetchLocal(self::CALLABLE);

        if (is_callable($callable)) {
            $callable($this->getResult());
        }
    }
}