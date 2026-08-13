<?php

namespace libReplay;

use libasyncio\s3\S3StorageCredentials;
use NetherGames\NGEssentials\NGEssentials;
use pocketmine\plugin\PluginBase;
use function getenv;

class S3Provider
{
    private static ?S3StorageCredentials $credentials = null;

    public function __construct(PluginBase $plugin)
    {
        $accessKey = getenv("REPLAYS_ACCESS_KEY");
        $secretKey = getenv("REPLAYS_SECRET_KEY");
        $region = getenv("REPLAYS_REGION");
        $endpoint = getenv("REPLAYS_ENDPOINT");
        $bucket = getenv("REPLAYS_BUCKET");

        if (NGEssentials::isInDevelopmentMode()) {
            $config = $plugin->getConfig();

            if ($accessKey === false) {
                $accessKey = $config->getNested("replays.access-key");
            }
            if ($secretKey === false) {
                $secretKey = $config->getNested("replays.secret-key");
            }
            if ($region === false) {
                $region = $config->getNested("replays.region");
            }
            if ($endpoint === false) {
                $endpoint = $config->getNested("replays.endpoint");
            }
            if ($bucket === false) {
                $bucket = $config->getNested("replays.bucket");
            }
        }

        if ($accessKey !== false && $secretKey !== false && $region !== false && $endpoint !== false && $bucket !== false) {
            // @phpstan-ignore-next-line
            self::$credentials = new S3StorageCredentials((string)$bucket, (string)$accessKey, (string)$secretKey, (string)$region, (string)$endpoint);
        }
    }

    public static function getStorageCredentials(): ?S3StorageCredentials
    {
        return self::$credentials;
    }
}