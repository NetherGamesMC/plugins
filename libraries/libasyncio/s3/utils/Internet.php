<?php

namespace libasyncio\s3\utils;

use pocketmine\utils\InternetException;
use pocketmine\utils\InternetRequestResult;

class Internet extends \pocketmine\utils\Internet
{
    /**
     * @param list<string> $extraHeaders
     */
    public static function putURL(string $page, string $args, int $timeout = 10, array $extraHeaders = [], &$err = null): ?InternetRequestResult
    {
        try {
            return self::simpleCurl($page, $timeout, $extraHeaders, [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $args
            ]);
        } catch (InternetException $ex) {
            $err = $ex->getMessage();
            return null;
        }
    }
}