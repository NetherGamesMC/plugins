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

namespace libasyncio\s3\task;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use libasyncio\s3\response\Error;
use libasyncio\s3\response\S3DeletedObject;
use libasyncio\s3\response\S3ObjectIdentifier;
use libasyncio\s3\S3StorageCredentials;
use libasyncio\s3\S3StorageError;
use pocketmine\utils\Internet;
use RuntimeException;

class S3ObjectBulkDeleteTask extends S3ConnectorTask
{
    /** @var string */
    private string $objectIdentifier;

    /**
     * @param S3ObjectIdentifier[] $objectIdentifier
     */
    public function setObjectIdentifiers(array $objectIdentifier): self
    {
        $serialized = igbinary_serialize($objectIdentifier);
        if (!is_string($serialized)) {
            throw new RuntimeException("Serialized array must always be string");
        }

        $this->objectIdentifier = $serialized;
        return $this;
    }

    /**
     * @throws DOMException
     */
    public function executeTask(): void
    {
        /** @var S3ObjectIdentifier[] $objectIdentifier */
        $objectIdentifier = igbinary_unserialize($this->objectIdentifier);

        $this->setResult(self::deleteObjects($this->getCredentials(), $objectIdentifier));
    }

    /**
     * @param S3StorageCredentials $credentials
     * @param S3ObjectIdentifier[] $objectIdentifiers
     * @return S3DeletedObject[]
     * @throws DOMException
     */
    public static function deleteObjects(S3StorageCredentials $credentials, array $objectIdentifiers): array
    {
        $xml = new DOMDocument("1.0", "UTF-8");
        $xmlDelete = $xml->createElement("Delete");

        foreach ($objectIdentifiers as $object) {
            $xmlObject = $xml->createElement("Object");
            $xmlObject->appendChild($xml->createElement("Key", $object->getObjectKey()));

            if (!empty($object->getObjectVersion())) {
                $xmlVersionId = $xml->createElement("VersionId", $object->getObjectVersion());
                $xmlObject->appendChild($xmlVersionId);
            }

            $xmlDelete->appendChild($xmlObject);
        }

        $xml->appendChild($xmlDelete);

        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;

        $contents = $xml->saveXML();
        if (!is_string($contents)) {
            throw new S3StorageError("XML must be a string, " . gettype($contents) . " returned.", S3StorageError::INTERNAL_ERROR);
        }

        $md5sum = base64_encode(md5($contents, true));
        $sha256sum = hash('sha256', $contents);

        $endpoint = $credentials->getEndpoint();
        $headers = $credentials->getS3OperationHeader($endpoint, self::MODE_POST, '/', [
            'x-amz-content-sha256' => $sha256sum,
        ], [
            'Content-MD5' => $md5sum,
            'Content-Type' => 'application/octet-stream'
        ], $queryString = self::getParameters([
            'delete' => '',
        ]));

        $result = Internet::postURL("https://$endpoint/{$credentials->getBucket()}/?$queryString", $contents, 10 * 60, $headers, $err);
        if ($result === null) {
            throw new S3StorageError("Connection failed, " . $err, S3StorageError::NETWORK_ERROR);
        }

        if ($result->getCode() === 200) {
            $domNode = new DOMDocument("1.0", "UTF-8");
            $domNode->loadXML($result->getBody());

            /** @var S3DeletedObject[] $deletedResults */
            $deletedResults = [];

            $firstNode = $domNode->firstChild;
            if (!($firstNode instanceof DOMNode)) {
                throw new S3StorageError("First XML response child must always be a DOMNode, Debug Info: " . (empty($result->getBody()) ? ' -- EMPTY --' : $result->getBody()) . ', Headers: ' . json_encode($result->getHeaders()), S3StorageError::STORAGE_ERROR);
            }

            /** @var DOMElement $node */
            foreach ($firstNode->childNodes as $node) {
                if ($node->nodeName === "Deleted") {
                    $objectName = $node->getElementsByTagName("Key")[0];
                    $versionId = $node->getElementsByTagName("VersionId")[0];
                    $deleteMarker = $node->getElementsByTagName("DeleteMarker")[0];
                    $deleteMarkerVersionId = $node->getElementsByTagName("DeleteMarkerVersionId")[0];

                    $deletedResults[] = new S3DeletedObject($objectName->nodeValue, $versionId?->nodeValue, (bool)$deleteMarker?->nodeValue, $deleteMarkerVersionId?->nodeValue);
                } else if ($node->nodeName === "Error") {
                    $objectName = $node->getElementsByTagName("Key")[0];
                    $errorCode = $node->getElementsByTagName("Code")[0]?->nodeValue ?? '';
                    $errorMessage = $node->getElementsByTagName("Message")[0]?->nodeValue ?? '';
                    $versionId = $node->getElementsByTagName("VersionId")[0]?->nodeValue ?? '';

                    $deletedResults[] = new S3DeletedObject($objectName->nodeValue, error: new Error($objectName->nodeValue, $errorCode, $errorMessage, $versionId));
                }
            }

            return $deletedResults;
        } else {
            throw new S3StorageError("Storage error [Code: {$result->getCode()}], Debug Info: " . (empty($result->getBody()) ? ' -- EMPTY --' : $result->getBody()) . ', Headers: ' . json_encode($result->getHeaders()), S3StorageError::STORAGE_ERROR);
        }
    }
}
