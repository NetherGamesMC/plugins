<?php

declare(strict_types=1);

namespace survivalgames;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\utils\TypeArena;
use libminigames\utils\TypeArenaTrait;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

/**
 * Just to separate the voting mechanism and the arena.
 */
abstract class SGTypeArena extends Arena implements TypeArena
{
    use TypeArenaTrait;

    public const TYPE_NORMAL = 0;   // Closing circles, player tags and events are available.
    public const TYPE_HARDCORE = 1; // Closing circle and events are available, only player tag are invisible.

    public const FALSE = 0;
    public const TRUE = 1;
    public const NONE = -1;

    /** @var bool[] */
    private array $deathVotes = [];

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_NORMAL => "Normal",
            self::TYPE_HARDCORE => "Hardcore"
        ];
    }

    public function checkDeathmatchVotes(): int
    {
        $voteCount = count($this->deathVotes);

        if ($voteCount === 0) {
            return self::NONE;
        }

        if ((count($this->getAlivePlayers()) / 2) < $voteCount) {
            return self::TRUE;
        }

        return self::FALSE;
    }

    public function addDeathmatchVote(Player $player): void
    {
        if (isset($this->deathVotes[$player->getName()])) {
            $player->sendMessage(TextFormat::RED . "You have already voted for deathmatch");
        } else {
            $this->deathVotes[$player->getName()] = true;

            $player->sendMessage(TextFormat::GREEN . "You have voted for deathmatch!");
        }
    }

    public function removeDeathmatch(Player $pl): void
    {
        unset($this->deathVotes[$pl->getName()]);
    }

    public function isNormal(): bool
    {
        return $this->getType() === self::TYPE_NORMAL;
    }

    /**
     * @return SurvivalGames
     */
    public function getPlugin(): Minigame
    {
        /** @var SurvivalGames $plugin */
        $plugin = parent::getPlugin();

        return $plugin;
    }
}