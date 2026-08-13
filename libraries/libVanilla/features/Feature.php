<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libVanilla\features;

use BadMethodCallException;
use libVanilla\LibVanillaItems;
use libVanilla\session\PlayerSessionManager;
use pocketmine\plugin\PluginBase;

abstract class Feature
{
    protected bool $registered = false;

    public function register(PluginBase $plugin): void
    {
        if ($this->registered) {
            return;
        }
        // Attempt to set up the session manager if needed
        PlayerSessionManager::register($plugin);
        $this->setup($plugin);

        $this->registered = true;

        // After setting this feature as registered, ensure the LibVanillaItems registry is initialized
        LibVanillaItems::ensureInitialization();
    }


    public function isRegistered(): bool
    {
        return $this->registered;
    }

    abstract protected function setup(PluginBase $plugin): void;

    /**
     * Throws an exception if the feature isn't registered
     */
    protected function verifyRegistration(): void
    {
        if (!$this->registered) {
            throw new BadMethodCallException("Feature requires registration before calling a method");
        }
    }
}