<?php

namespace meltdown\arena\handler;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use meltdown\arena\MDArena;

class ModifierHandler
{
    /** @var int PVP is enabled */
    public const MODIFIER_PVP = 1;

    /** @var int Players get speed II effect */
    public const MODIFIER_SPEED = 2;

    /** @var MDArena */
    private MDArena $arena;

    /** @var int */
    private int $activeModifier = 0;

    /** @var array<int, int> [player entity id => modifier] */
    private array $modifierVotes = [];

    /**
     * @param MDArena $arena
     */
    public function __construct(MDArena $arena)
    {
        $this->arena = $arena;
    }

    /**
     * @return int[]
     */
    public function getPotentialModifiers(): array
    {
        return [self::MODIFIER_PVP, self::MODIFIER_SPEED];
    }

    /**
     * @param int $modifier
     * @return bool
     */
    public function isModifierActive(int $modifier): bool
    {
        return $this->getActiveModifier() === $modifier;
    }

    /**
     * @return int
     */
    public function getActiveModifier(): int
    {
        return $this->activeModifier;
    }

    /**
     * @param Player $player
     * @param int $modifier
     */
    public function addModifierVote(Player $player, int $modifier): void
    {
        $this->modifierVotes[$player->getId()] = $modifier;
    }

    public function checkModifierVotes(): void
    {
        $votes = array_count_values($this->modifierVotes);
        if (count($votes) > 0) {
            $mostVoted = array_keys($votes, max($votes));
            $pickedModifier = $mostVoted[array_rand($mostVoted)];
            $this->activeModifier = $pickedModifier;
            $voteCount = $votes[$pickedModifier];
            $this->getArena()->broadcastMessage($this->getModifierPrettyName($pickedModifier) . TextFormat::GREEN . " modifier has been selected with $voteCount vote" . ($voteCount > 1 ? "s" : "") . "!", true);
        } else {
            $this->getArena()->broadcastMessage(TextFormat::GRAY . "No modifiers" . TextFormat::GREEN . " have been selected. A classic game!", true);
        }
    }

    /**
     * @return MDArena
     */
    public function getArena(): MDArena
    {
        return $this->arena;
    }

    /**
     * @param int $modifier
     * @return string|null
     */
    public function getModifierPrettyName(int $modifier): ?string
    {
        return match ($modifier) {
            self::MODIFIER_PVP => TextFormat::AQUA . "PVP",
            self::MODIFIER_SPEED => TextFormat::AQUA . "Speed",
            default => null
        };
    }
}
