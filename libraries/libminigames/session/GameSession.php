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
declare(strict_types=1);

namespace libminigames\session;

use libminigames\utils\Icon;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use Ramsey\Uuid\UuidInterface;
use function microtime;
use function round;
use function str_contains;

/**
 * Holds generic, engine-level runtime state for a player that is relevant to minigames.
 *
 * <p>This is the only player-state abstraction used by libminigames; NetherGames-specific
 * state (ranks, parties, economy, ...) is handled externally by listening to the events fired
 * by this library.
 */
final class GameSession
{
    /** @var \WeakMap<Player, GameSession>|null */
    private static ?\WeakMap $sessions = null;

    private bool $energized = false;
    private bool $armorInvisible = false;
    private int $inputMode = InputMode::MOUSE_KEYBOARD;
    private bool $classicUi = false;
    private bool $popupsEnabled = true;
    private bool $touchOnlyPreference = false;
    private float $lastMove;
    private ?UuidInterface $groupUUID = null;

    /**
     * @param \WeakReference<Player> $playerReference
     */
    private function __construct(private \WeakReference $playerReference)
    {
        $this->lastMove = microtime(true);
    }

    public static function getSession(Player $player): self
    {
        $weakMap = self::$sessions;
        if ($weakMap === null) {
            /** @var \WeakMap<Player, GameSession> $weakMap */
            $weakMap = new \WeakMap();
            self::$sessions = $weakMap;
        }

        $session = $weakMap[$player] ?? null;
        if ($session === null) {
            $session = new self(\WeakReference::create($player));
            $weakMap[$player] = $session;
        }

        return $session;
    }

    public function getPlayer(): ?Player
    {
        /** @var Player|null $player */
        $player = $this->playerReference->get();

        return $player;
    }

    public function isEnergized(): bool
    {
        return $this->energized;
    }

    public function setEnergized(bool $value = true): void
    {
        $this->energized = $value;

        if ($value && ($player = $this->getPlayer()) !== null) {
            $hungerManager = $player->getHungerManager();
            $hungerManager->setFood($hungerManager->getMaxFood());
            $hungerManager->setExhaustion(0.0);
        }
    }

    public function isArmorInvisible(): bool
    {
        return $this->armorInvisible;
    }

    public function setArmorInvisible(bool $value = true): void
    {
        $this->armorInvisible = $value;
    }

    public function getInputMode(): int
    {
        return $this->inputMode;
    }

    public function setInputMode(int $inputMode): void
    {
        $this->inputMode = $inputMode;
    }

    public function isClassicUi(): bool
    {
        return $this->classicUi;
    }

    public function setClassicUi(bool $classicUi): void
    {
        $this->classicUi = $classicUi;
    }

    public function isPopupsEnabled(): bool
    {
        return $this->popupsEnabled;
    }

    public function setPopupsEnabled(bool $popupsEnabled): void
    {
        $this->popupsEnabled = $popupsEnabled;
    }

    public function isTouchOnlyPreference(): bool
    {
        return $this->touchOnlyPreference;
    }

    public function setTouchOnlyPreference(bool $touchOnlyPreference): void
    {
        $this->touchOnlyPreference = $touchOnlyPreference;
    }

    /**
     * The UUID of the group this player belongs to, if any.
     *
     * <p>Players sharing the same group UUID are treated as a single join unit by the engine.
     * The UUID is supplied by the group owner (e.g. a party system); libminigames never
     * generates or derives it - it only stores and compares the value. <code>null</code> means
     * the player is not part of a group. The engine does not compute group sizes; group owners
     * pass the expected size explicitly when queueing (see {@link \libminigames\Minigame::joinArena()}).
     */
    public function getGroupUUID(): ?UuidInterface
    {
        return $this->groupUUID;
    }

    public function setGroupUUID(?UuidInterface $groupUUID): void
    {
        $this->groupUUID = $groupUUID;
    }

    public function hasGroup(): bool
    {
        return $this->groupUUID !== null;
    }

    public function recordMove(): void
    {
        $this->lastMove = microtime(true);
    }

    public function getLastMoveTime(): float
    {
        return $this->lastMove;
    }

    public function playSound(string $sound, float $volume = 1.0, float $pitch = 1.0): void
    {
        $player = $this->getPlayer();
        if ($player === null) {
            return;
        }

        $location = $player->getLocation();

        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
            $sound,
            $location->x,
            $location->y,
            $location->z,
            $volume,
            $pitch,
            null
        ));
    }

    public function toggleGameRule(string $gameRule, bool $value): void
    {
        $player = $this->getPlayer();
        if ($player === null) {
            return;
        }

        $player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
            $gameRule => new BoolGameRule($value, false)
        ]));
    }

    public function setHealthTag(bool $bool = true): void
    {
        $player = $this->getPlayer();
        if ($player === null) {
            return;
        }

        $heartGlyph = Icon::get('heart');
        if ($heartGlyph === '') {
            return;
        }

        if ($bool) {
            $health = $player->getHealth();
            if ($health < 0.2) {
                $player->setScoreTag('§f§l0.1 §r' . $heartGlyph);
            } else {
                $player->setScoreTag('§f§l' . round($health / 2, 1) . ' §r' . $heartGlyph);
            }
        } else {
            $player->setScoreTag('');
        }
    }

    public function getHealthTag(): bool
    {
        $player = $this->getPlayer();
        if ($player === null) {
            return false;
        }

        $glyph = Icon::get('heart');
        $tag = $player->getScoreTag();

        return $glyph !== '' && $tag !== null && str_contains($tag, $glyph);
    }

    public function updateHealthTag(): void
    {
        $this->setHealthTag($this->getHealthTag());
    }
}