<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Driesboy
 *
 */

namespace libminigames\utils;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_count_values;
use function array_key_first;
use function array_keys;
use function array_rand;
use function arsort;
use function count;
use const SORT_NUMERIC;

/**
 * @see TypeArena
 */
trait TypeArenaTrait
{
    /** @var int[] */
    private array $typeVotes = [];
    /** @var int */
    private int $type;

    public function addTypeVote(Player $player, int $type): void
    {
        $this->typeVotes[$player->getId()] = $type;
    }

    public function getTypeName(): string
    {
        return static::getTypes()[$this->getType()];
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function checkTypeVotes(): void
    {
        $typeVotes = $this->getTypeVotes();
        if (count($typeVotes) > 0) {
            arsort($typeVotes, SORT_NUMERIC);

            $type = array_key_first($typeVotes);
            $votes = $typeVotes[$type];
            $typeName = static::getTypes()[$type];

            $this->broadcastMessage(TextFormat::GOLD . $typeName . ' mode has won with ' . $votes . ' vote' . ($votes > 1 ? 's' : '') . '!', true);
        } else {
            $typeIds = array_keys(static::getTypes());
            $type = $typeIds[array_rand($typeIds)];
            $typeName = static::getTypes()[$type];

            $this->broadcastMessage(TextFormat::GOLD . $typeName . ' mode has been randomly selected!', true);
        }

        $this->type = $type;

        unset($this->typeVotes);
    }

    /**
     * @return array<int, int>
     */
    public function getTypeVotes(): array
    {
        return array_count_values($this->typeVotes);
    }

    public function removeTypeVote(Player $player): void
    {
        unset($this->typeVotes[$player->getId()]);
    }
}