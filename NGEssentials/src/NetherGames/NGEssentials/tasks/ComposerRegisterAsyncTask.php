<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\tasks;

use pocketmine\scheduler\AsyncTask;
use const nethergames\COMPOSER_AUTOLOADER_PATH;

class ComposerRegisterAsyncTask extends AsyncTask
{
    /** @var string */
    private string $autoloaderPath;

    public function __construct()
    {
        $this->autoloaderPath = COMPOSER_AUTOLOADER_PATH;
    }

    public function onRun(): void
    {
        require $this->autoloaderPath;
    }
}