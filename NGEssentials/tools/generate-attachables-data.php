<?php

use NetherGames\NGEssentials\utils\SkinUtils;

require dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 3) {
    echo "Usage: php generate-attachables-data.php <input-geometry> <input-texture> <output>" . PHP_EOL;
    exit(1);
}

$inputGeometry = file_get_contents($argv[1]);
$inputTexture = file_get_contents($argv[2]);
$outputPath = $argv[3];

$texture = SkinUtils::getTextureFromString($inputTexture);
file_put_contents($outputPath, json_encode([
    "attachables" => [
        "geometry" => json_decode($inputGeometry),
        "texture" => base64_encode($texture),
    ],
]));
