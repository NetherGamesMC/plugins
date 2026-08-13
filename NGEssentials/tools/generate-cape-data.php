<?php

use NetherGames\NGEssentials\utils\SkinUtils;

require dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 3) {
    echo "Usage: php generate-cape-data.php <input> <output>" . PHP_EOL;
    exit(1);
}

$input = file_get_contents($argv[1]);
$outputPath = $argv[2];

$texture = SkinUtils::getTextureFromString($input);
file_put_contents($outputPath, json_encode([
    "cape" => [
        "data" => base64_encode($texture),
    ],
]));
