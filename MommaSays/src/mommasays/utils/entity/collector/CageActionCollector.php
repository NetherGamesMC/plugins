<?php
/**
 *        __  __                                  _____
 *       |  \/  |                                / ____|
 *  __  _| \  / | ___  _ __ ___  _ __ ___   __ _| (___   __ _ _   _ ___
 *  \ \/ / |\/| |/ _ \| '_ ` _ \| '_ ` _ \ / _` |\___ \ / _` | | | / __|
 *   >  <| |  | | (_) | | | | | | | | | | | (_| |____) | (_| | |_| \__ \
 *  /_/\_\_|  |_|\___/|_| |_| |_|_| |_| |_|\__,_|_____/ \__,_|\__, |___/
 *                                                             __/ |
 *                                                            |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author TobiasDev
 *
 */

namespace mommasays\utils\entity\collector;

use libasyncio\blocks\AsyncBlockManager;
use libasyncio\blocks\Selection;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class CageActionCollector
{
    /** @var Selection */
    private Selection $selection;

    /** @var World */
    private World $world;

    private function __construct(World $world)
    {
        $this->world = $world;
        $this->selection = new Selection();
    }

    /**
     * @param World $world
     * @return CageActionCollector
     * Creates a new instance of the BatchActionCollector
     */
    public static function create(World $world): self
    {
        return new self($world);
    }

    /**
     * @param Vector3 $vector
     * @param Block $block
     */
    public function add(Vector3 $vector, Block $block): void
    {
        $this->selection->addBlock($vector, $block);
    }

    /**
     * Executes all block actions that are saved in this instance in one bulk change
     */
    public function execute(): void
    {
        AsyncBlockManager::executeSet($this->selection, $this->world);
    }

    /**
     * @return Selection
     */
    public function getBlockList(): Selection
    {
        return $this->selection;
    }

    /**
     * @return World
     */
    public function getWorld(): World
    {
        return $this->world;
    }
}