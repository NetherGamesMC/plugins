<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author xBeastMode
 *
 */
declare(strict_types=1);

namespace skywars\kits;


class Kit
{
    /** @var Item[] */
    private array $items;
    /** @var int */
    private int $id;
    /** @var string */
    private string $name;

    public function __construct(string $name, int $kitId, array $items)
    {
        $this->name = $name;
        $this->id = $kitId;
        $this->items = $items;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}