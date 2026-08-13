<?php
/**
 *   _ _ _                                _
 *  | (_) |                              (_)
 *  | |_| |__   __ _ ___ _   _ _ __   ___ _  ___
 *  | | | '_ \ / _` / __| | | | '_ \ / __| |/ _ \
 *  | | | |_) | (_| \__ \ |_| | | | | (__| | (_) |
 *  |_|_|_.__/ \__,_|___/\__, |_| |_|\___|_|\___/
 *                        __/ |
 *                       |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author larryTheCoder
 *
 */
declare(strict_types=1);

namespace libasyncio\s3;

class S3StorageCredentials
{
    public function __construct(
        private string $bucket,
        private string $accessKey,
        private string $secretKey,
        private string $region,
        private string $endpoint
    )
    {
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @param string[] $amzHeaders Amazon headers. (Requires: x-amz-date and x-amz-content-sha256)
     * @param string[] $headers The headers that were used to send the request to s3.
     * @return list<string>
     */
    public function getS3OperationHeader(string $endpoint, string $method, string $uri, array $amzHeaders = [], array $headers = [], string $queryString = ''): array
    {
        $uri = '/' . $this->getBucket() . $uri;
        $amzHeaders = array_merge(['x-amz-date' => gmdate('Ymd\THis\Z'), 'x-amz-content-sha256' => hash("sha256", "")], $amzHeaders);
        $headers = array_merge(['Host' => $endpoint, 'Date' => gmdate('D, d M Y H:i:s T')], $headers);

        $signature = self::getSignatureV4($this, $amzHeaders, $headers, $method, $uri, $queryString);

        $parsedHeaders = [];
        foreach (array_merge($amzHeaders, $headers, ['Authorization' => $signature]) as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $parsedHeaders[] = "$key: $value";
        }

        return $parsedHeaders;
    }

    /**
     * Get the AWS S3 signature for the header, method, and the object path.
     *
     * @param string[] $amzHeaders Amazon headers. (Requires: x-amz-date and x-amz-content-sha256)
     * @param string[] $headers The headers that were used to send the request to s3.
     * @param string $method Method of the request 'POST', 'GET', 'HEAD', 'DELETE', 'PUT'
     * @param string $uri The location of an object that were used in this operation.
     * @param string $queryString The canonical parameter query string array, (e.g.: ?foo=bar&marko=polo)
     */
    private static function getSignatureV4(S3StorageCredentials $credentials, array $amzHeaders, array $headers, string $method, string $uri, string $queryString): string
    {
        $region = $credentials->getRegion();
        $service = 's3';

        $algorithm = 'AWS4-HMAC-SHA256';
        /** @var string[] $combinedHeaders */
        $combinedHeaders = [];

        $amzDateStamp = substr($amzHeaders['x-amz-date'], 0, 8);

        // CanonicalHeaders
        foreach ($headers as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }
        foreach ($amzHeaders as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }
        self::sortMetaHeaders($combinedHeaders);

        // Payload
        $amzPayload = [$method];

        $qsPos = strpos($uri, '?');
        $amzPayload[] = ($qsPos === false ? $uri : substr($uri, 0, $qsPos));

        $amzPayload[] = $queryString;
        // add header as string to requests
        foreach ($combinedHeaders as $k => $v) {
            $amzPayload[] = $k . ':' . $v;
        }
        // add a blank entry, so we end up with an extra line break
        $amzPayload[] = '';
        // SignedHeaders
        $amzPayload[] = implode(';', array_keys($combinedHeaders));
        // payload hash
        $amzPayload[] = $amzHeaders['x-amz-content-sha256'];
        // request as string
        $amzPayloadStr = implode("\n", $amzPayload);

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = implode("\n", [$algorithm, $amzHeaders['x-amz-date'],
            implode('/', $credentialScope), hash('sha256', $amzPayloadStr)]);

        // Make Signature
        $kSecret = 'AWS4' . $credentials->getSecretKey();
        $kDate = hash_hmac('sha256', $amzDateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSignStr, $kSigning);

        return $algorithm . ' ' . implode(',', [
                'Credential=' . $credentials->getAccessKey() . '/' . implode('/', $credentialScope),
                'SignedHeaders=' . implode(';', array_keys($combinedHeaders)),
                'Signature=' . $signature,
            ]);
    }

    /**
     * @param string[] $array
     */
    public static function sortMetaHeaders(array &$array): void
    {
        uksort($array, static function (string $a, string $b): int {
            $lenA = strlen($a);
            $lenB = strlen($b);
            $minLen = min($lenA, $lenB);
            $compare = strncmp($a, $b, $minLen);
            if ($lenA == $lenB) {
                return $compare;
            }
            if (0 == $compare) {
                return $lenA < $lenB ? -1 : 1;
            }
            return $compare;
        });
    }
}