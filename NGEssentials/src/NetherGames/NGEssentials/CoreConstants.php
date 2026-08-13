<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials;

use function define;
use function defined;
use function dirname;
use function getenv;

// composer autoload doesn't use require_once and also pthreads can inherit things
if (defined('nethergames\_CORE_CONSTANTS_INCLUDED')) {
    return;
}
define('nethergames\_CORE_CONSTANTS_INCLUDED', true);

define('nethergames\COMPOSER_AUTOLOADER_PATH', dirname(__FILE__, 4) . '/vendor/autoload.php');
define('nethergames\API_URI', ($ip = getenv('API_IP')) === false ? 'https://api.ngmc.co' : $ip);