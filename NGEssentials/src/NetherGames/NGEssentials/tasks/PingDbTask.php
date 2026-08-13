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

namespace NetherGames\NGEssentials\tasks;

use mysqli;
use NetherGames\NGEssentials\NGEssentials;
use pmmp\thread\ThreadSafeArray;
use pocketmine\scheduler\AsyncTask;

class PingDbTask extends AsyncTask
{
    /** @var ThreadSafeArray */
    private ThreadSafeArray $credentials; //TODO: Add typed property back when pthreads is fixed

    public function __construct(array $credentials)
    {
        $this->credentials = ThreadSafeArray::fromArray($credentials);
    }

    public function onRun(): void
    {
        $mysqli = @new mysqli($this->credentials[0], $this->credentials[1], $this->credentials[2], $this->credentials[3]);
        $this->setResult(!$mysqli->connect_error && $mysqli->ping());
    }

    public function onCompletion(): void
    {
        $plugin = NGEssentials::getInstance();

        if ($this->getResult() ?? false) {
            $plugin->getLogger()->emergency('Database connection re-established - restarting server...');
            $plugin->getServer()->shutdown();
        } else {
            $plugin->getLogger()->alert('MySQL Database connection failed!');
        }
    }
}
