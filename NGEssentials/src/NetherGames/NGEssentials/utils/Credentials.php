<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\utils;

use NetherGames\NGEssentials\kafka\KafkaUtility;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\utils\VoteManager;
use pocketmine\utils\Config;
use function getenv;

class Credentials
{
    public static function sendCredentials(NGEssentials $plugin, Config $config): MySQLCredentials
    {
        if (NGEssentials::isInDevelopmentMode() || NGEssentials::isInHybridDevelopmentMode()) {
            $voteKey = $config->getNested('vote_key');

            $kafkaEndpoint = $config->getNested('kafka_ip');

            $host = $config->getNested('database.host');
            $user = $config->getNested('database.username');
            $password = $config->getNested('database.password');
            $schema = $config->getNested('database.schema');

            KafkaUtility::setKafkaEndpoint($kafkaEndpoint);
        } else {
            $voteKey = getenv('VOTE_KEY');

            $host = getenv('DB_HOST');
            $user = getenv('DB_USER');
            $password = getenv('DB_PASSWORD');
            $schema = getenv('DB_SCHEMA');
        }

        VoteManager::setVoteKey($voteKey);

        return new MySQLCredentials($plugin, [$host, $user, $password, $schema]);
    }
}
