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

namespace NetherGames\NGEssentials\player\pets;

use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use ReflectionClass;
use function preg_replace;
use function str_ends_with;
use function str_replace;
use function strtolower;
use function substr;
use function ucwords;

readonly class PetCreator
{
    private string $saveId;

    /**
     * @param class-string<Entity> $petClass
     * @param string[] $permissions
     */
    public function __construct(private string $petClass, private array $permissions = [])
    {
        $this->saveId = $this->generateSaveId();
    }

    public function create(Player $owner): Entity
    {
        return new $this->petClass($owner->getLocation(), $owner);
    }

    private function generateSaveId(): string
    {
        $refClass = new ReflectionClass($this->petClass);
        $typeName = $refClass->getShortName();
        $typeName = str_ends_with($typeName, "Pet") ? substr($typeName, 0, -3) : $typeName;
        return strtolower(preg_replace("/(?<!^)[A-Z]/", "_$0", $typeName));
    }

    public function getSaveId(): string
    {
        return $this->saveId;
    }

    public function getDisplayableName(): string
    {
        return ucwords(str_replace('_', ' ', $this->saveId));
    }

    public function hasPermission(Player $player): bool
    {
        return Permissions::hasPermission($player, $this->permissions);
    }
}