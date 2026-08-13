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

namespace NetherGames\NGEssentials\player\cosmetics\types;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\utils\PlayerCosmeticEntry;
use NetherGames\NGEssentials\player\cosmetics\utils\PlayerCosmeticStatus;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_diff;
use function array_filter;
use function array_map;
use function in_array;
use function ksort;
use function sort;
use function usort;
use const SORT_NUMERIC;

abstract class Cosmetic
{
    private const SELECTED = 0;

    /** @var CosmeticEntry[] */
    protected array $entries = [];

    public function __construct(private readonly int $saveId, protected CosmeticHandler $handler)
    {

    }

    /**
     * @param Player[] $players
     */
    protected final function getRandomSelectedEntry(array $players): ?CosmeticEntry
    {
        /** @var CosmeticEntry[] $entries */
        $entries = array_filter(array_map(function (Player $player): ?CosmeticEntry {
            return $this->getSelectedEntry($player);
        }, $players));

        if (count($entries) === 0) {
            return null;
        }

        return $entries[array_rand($entries)];
    }

    /**
     * Filters out the entries meant for this cosmetic type and sorts them by id.
     *
     * @param CosmeticEntry[] $entries
     * @internal
     */
    public final function setEntries(array $entries): void
    {
        foreach (array_filter($entries, fn(CosmeticEntry $entry): bool => $entry->type === $this->saveId) as $entry) {
            $this->entries[$entry->id] = $entry;
        }

        ksort($this->entries, SORT_NUMERIC);
        $this->registerEntries($this->entries);
    }

    /**
     * @param CosmeticEntry[] $entries
     */
    protected function registerEntries(array $entries): void
    {
        // NOOP
    }

    /**
     * @return CosmeticEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @internal
     */
    public function getSelectedEntry(Player $player): ?CosmeticEntry
    {
        return ($cosmeticId = $this->getSelectedId($player)) === null ? null : $this->getEntry($cosmeticId);
    }

    protected function getSelectedId(Player $player): ?int
    {
        return $this->handler->getPlugin()->getPlayerData()->getArray($player, PlayerData::COSMETICS)[self::SELECTED][$this->saveId] ?? null;
    }

    protected function setSelected(Player $player, ?CosmeticEntry $entry): void
    {
        $playerData = $this->handler->getPlugin()->getPlayerData();
        $cosmetics = $playerData->getArray($player, PlayerData::COSMETICS);

        if (isset($cosmetics[self::SELECTED][$this->saveId])) {
            if ($entry === null) {
                unset($cosmetics[self::SELECTED][$this->saveId]);
            } else {
                $cosmetics[self::SELECTED][$this->saveId] = $entry->id;
            }
        } else if ($entry !== null) {
            if (!isset($cosmetics[self::SELECTED])) {
                $cosmetics[self::SELECTED] = [];
                ksort($cosmetics, SORT_NUMERIC);
            }

            $cosmetics[self::SELECTED][$this->saveId] = $entry->id;
            ksort($cosmetics[self::SELECTED], SORT_NUMERIC);
        }

        $playerData->setValue($player, PlayerData::COSMETICS, $cosmetics, true);
    }

    public function getEntry(int $cosmeticId): ?CosmeticEntry
    {
        return $this->entries[$cosmeticId] ?? null;
    }

    /**
     * @return int[]
     */
    public function getCrateIds(?CosmeticRarity $rarity = null): array
    {
        return array_map(
            static fn(CosmeticEntry $entry): int => $entry->id,
            array_filter($this->entries, static fn(CosmeticEntry $entry): bool => $entry->crateAvailable && ($rarity === null || $entry->rarity === $rarity))
        );
    }

    /**
     * @return int[]
     */
    public function getAvailableCrateIds(Player $player, ?CosmeticRarity $rarity = null): array
    {
        return array_diff($this->getCrateIds($rarity), $this->getOwnedCosmeticIds($player));
    }

    /**
     * @return int[]
     */
    protected function getOwnedCosmeticIds(Player $player): array
    {
        return $this->handler->getPlugin()->getPlayerData()->getArray($player, PlayerData::COSMETICS)[$this->saveId] ?? [];
    }

    /**
     * @return PlayerCosmeticEntry[]
     */
    protected function getPlayerCosmeticEntries(Player $player): array
    {
        $selectedId = $this->getSelectedId($player);
        $ownedIds = $this->getOwnedCosmeticIds($player);

        return array_map(static fn(CosmeticEntry $entry) => new PlayerCosmeticEntry($entry, match (true) {
            $selectedId === $entry->id => PlayerCosmeticStatus::SELECTED,
            in_array($entry->id, $ownedIds, true) => PlayerCosmeticStatus::UNLOCKED,
            default => PlayerCosmeticStatus::LOCKED
        }), $this->entries);
    }

    abstract public function getName(): string;

    public function addCosmeticsToForm(SimpleForm $form, Player $player, callable $callable): void
    {
        $entries = $this->getPlayerCosmeticEntries($player);

        if (!Permissions::isStaff($player)) {
            $entries = array_filter($entries, static fn(PlayerCosmeticEntry $entry): bool => $entry->status !== PlayerCosmeticStatus::LOCKED);
        }

        usort($entries, static function (PlayerCosmeticEntry $a, PlayerCosmeticEntry $b): int {
            return $a->status <=> $b->status;
        });

        foreach ($entries as $entry) {
            $this->addCosmeticEntryToForm($form, $player, $entry, $callable);
        }
    }

    protected function addCosmeticEntryToForm(SimpleForm $form, Player $player, PlayerCosmeticEntry $playerEntry, callable $callable): void
    {
        $isStaff = Permissions::isStaff($player);

        $color = match (true) {
            $playerEntry->status === PlayerCosmeticStatus::SELECTED => TextFormat::GREEN,
            $playerEntry->status === PlayerCosmeticStatus::UNLOCKED || $isStaff => TextFormat::YELLOW,
            default => throw new RuntimeException('Invalid status')
        };

        $onClick = function (Player $player) use ($playerEntry, $isStaff, $callable) {
            if ($playerEntry->status === PlayerCosmeticStatus::LOCKED && !$isStaff) {
                $callable($player);
            } else {
                $entry = $playerEntry->entry;

                if ($playerEntry->status === PlayerCosmeticStatus::SELECTED) {
                    $player->sendMessage('§aTurned the §6' . $entry->name . ' §acosmetic off.');
                    $this->setSelected($player, null);
                } else {
                    $player->sendMessage('§aSelected §6' . $entry->name . ' §afor the §6' . $this->getName() . ' §acosmetic.');
                    $this->setSelected($player, $entry);
                }

                if ($this->onSelect($player)) {
                    $callable($player);
                }
            }
        };

        $entry = $playerEntry->entry;
        $button = $entry->imageType === null ? (
        new Button($color . $entry->name, $onClick)
        ) : (
        new ImageButton($color . $entry->name, $entry->imageType, $entry->imageSource, $onClick)
        );

        $form->addButton($button);
    }

    public function give(Player $player, CosmeticEntry $entry): void
    {
        if ($entry->type !== $this->saveId) {
            throw new RuntimeException('Cosmetic entry is not for this type');
        }

        $playerData = $this->handler->getPlugin()->getPlayerData();
        $cosmetics = $playerData->getArray($player, PlayerData::COSMETICS);

        if (!isset($cosmetics[$this->saveId])) {
            $cosmetics[$this->saveId] = [];
            ksort($cosmetics, SORT_NUMERIC);
        }

        if (!in_array($entry->id, $cosmetics[$this->saveId], true)) {
            $cosmetics[$this->saveId][] = $entry->id;

            sort($cosmetics[$this->saveId], SORT_NUMERIC);

            $playerData->setValue($player, PlayerData::COSMETICS, $cosmetics, true);
        }
    }

    public function has(Player $player, CosmeticEntry $entry): bool
    {
        return in_array($entry->id, $this->getOwnedCosmeticIds($player), true);
    }

    public function getSaveId(): int
    {
        return $this->saveId;
    }

    public function showSkin(): bool
    {
        return false;
    }

    protected function getHandler(): CosmeticHandler
    {
        return $this->handler;
    }

    abstract public function getButton(Player $player, Closure $callable): Button;

    abstract public function getCrateAnimation(): string;

    /**
     * @return bool if the form should be sent again
     */
    protected function onSelect(Player $player): bool
    {
        return false;
    }
}
