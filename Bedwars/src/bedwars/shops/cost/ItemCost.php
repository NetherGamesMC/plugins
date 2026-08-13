<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars\shops\cost;

use bedwars\BWTeam;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function floor;

final class ItemCost
{
    private readonly int $teamAmount;

    public function __construct(
        private readonly CostType $type,
        private readonly int      $amount,
        ?int                      $teamAmount = null
    )
    {
        $this->teamAmount = $teamAmount ?? $this->amount;
    }

    public function getType(): CostType
    {
        return $this->type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTeamAmount(): int
    {
        return $this->teamAmount;
    }

    public function getAmountByGameType(BWTeam $team): int
    {
        if ($team->getArena()->getGameSettings()->hasFreeItems()) {
            return 0;
        }

        if ($team->getArena()->isVersus() && $this->amount >= 2) {
            return (int)floor($this->amount / 2);
        }

        return $team->getArena()->isTriosOrSquads() ? $this->teamAmount : $this->amount;
    }

    public function getName(): string
    {
        return $this->type->name;
    }

    public function getColor(): string
    {
        return $this->type->color;
    }

    /**
     * @return array{name: string, displayName: string, color: string, amount: int}
     */
    public function getAttributes(BWTeam $team): array
    {
        return [
            ...$this->type->getAttributes(),
            "amount" => $this->getAmountByGameType($team)
        ];
    }

    /**
     * Returns a formatted cost name (e.g., "4 Emeralds")
     */
    public function getDisplayName(BWTeam $team): string
    {
        $amount = $this->getAmountByGameType($team);
        if ($amount === 0) {
            return TextFormat::GREEN . "Free";
        }

        // pluralize name if amount isn't equal to 1
        $formattedName = $this->type->displayName . ($amount !== 1 ? "s" : "");
        return "{$this->getColor()}$amount $formattedName";
    }


    /**
     * Creates a cost item from the associated cost type and game type
     */
    public function asItem(BWTeam $team): Item
    {
        $amount = $this->getAmountByGameType($team);
        if ($amount === 0) {
            return VanillaItems::AIR()->setCount(0);
        }

        return $this->type->asItem($this->getAmountByGameType($team));
    }

    /**
     * Returns true if the player has sufficient funds for the item cost
     */
    public function contains(Player $player, BWTeam $team, ?int $multiplier = null): bool
    {
        if (($amount = $this->getAmountByGameType($team)) === 0) {
            return true;
        }

        return $this->type->contains($player, $amount * ($multiplier ?? 1));
    }
}