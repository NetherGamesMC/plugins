<?php

// Stage 1
$data = file_get_contents(__DIR__ . '/vendor/amphp/process/lib/functions.php');
$data = str_replace(
    [
        "const BIN_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin';",
        "const IS_WINDOWS = (PHP_OS & \"\\xDF\\xDF\\xDF\") === 'WIN';"
    ],
    [
        "if (!defined(\"Amp\Process\BIN_DIR\")) {\n" .
        "    define(\"Amp\Process\BIN_DIR\", __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin'); \n" .
        "}",
        "if (!defined(\"Amp\Process\IS_WINDOWS\")) {\n" .
        "    define(\"Amp\Process\IS_WINDOWS\", (PHP_OS & \"\\xDF\\xDF\\xDF\") === 'WIN'); \n" .
        "}",
    ], $data);
file_put_contents(__DIR__ . '/vendor/amphp/process/lib/functions.php', $data);

// Stage 2
$data = file_get_contents(__DIR__ . '/vendor/amphp/dns/lib/functions.php');
$data = str_replace("const LOOP_STATE_IDENTIFIER = Resolver::class;",
    "if (!defined(\"Amp\Dns\LOOP_STATE_IDENTIFIER\")) {\n" .
    "    define(\"Amp\Dns\LOOP_STATE_IDENTIFIER\", Resolver::class); \n" .
    "}", $data);
file_put_contents(__DIR__ . '/vendor/amphp/dns/lib/functions.php', $data);

// Stage 3
$data = file_get_contents(__DIR__ . '/vendor/amphp/socket/src/functions.php');
$data = str_replace("const LOOP_CONNECTOR_IDENTIFIER = Connector::class;",
    "if (!defined(\"Amp\Dns\LOOP_CONNECTOR_IDENTIFIER\")) {\n" .
    "    define(\"Amp\Dns\LOOP_CONNECTOR_IDENTIFIER\", Connector::class); \n" .
    "}", $data);
file_put_contents(__DIR__ . '/vendor/amphp/socket/src/functions.php', $data);