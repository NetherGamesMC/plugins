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

namespace NetherGames\NGEssentials\player\cosmetics\traits;

use Closure;
use InvalidArgumentException;
use NetherGames\NGEssentials\block\CustomBlockRegistry;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\block\Block;
use function array_filter;
use function array_map;

/**
 * @mixin Cosmetic
 */
trait CustomBlockCosmeticTrait
{
    /** @var array<string, Block> */
    private array $customBlockCache = [];

    /** @var list<string> */
    private array $registeredBlocks = [];

    private const CUSTOM_BLOCK = 'custom_block';

    private const CUSTOM_BLOCK_IDENTIFIER = 'identifier';
    private const CUSTOM_BLOCK_NAME = 'name';
    private const CUSTOM_BLOCK_TEXTURE = 'texture';
    private const CUSTOM_BLOCK_GEOMETRY = 'geometry';

    protected function getCustomBlock(CosmeticDataEntry $entry): Block
    {
        return $this->processBlock($entry, clone($this->customBlockCache[$entry->getHash()] ??= throw new InvalidArgumentException("Custom block for cosmetic $entry->id is not registered")));
    }

    abstract private function getBlockFunction(string $blockName, ?string $blockTexture, ?string $blockGeometry): Closure;

    private function getBlockIdentifier(CosmeticDataEntry $entry): string
    {
        $customBlockData = $entry->data[self::CUSTOM_BLOCK] ?? throw new InvalidArgumentException("Missing custom block data for cosmetic $entry->id");

        return $customBlockData[self::CUSTOM_BLOCK_IDENTIFIER] ?? throw new InvalidArgumentException("Missing block identifier for cosmetic $entry->id");
    }

    private function registerBlock(CosmeticDataEntry $entry): void
    {
        $customBlockData = $entry->data[self::CUSTOM_BLOCK] ?? throw new InvalidArgumentException("Missing custom block data for cosmetic $entry->id");

        $blockName = $customBlockData[self::CUSTOM_BLOCK_NAME] ?? throw new InvalidArgumentException("Missing block name for cosmetic $entry->id");
        $blockTexture = $customBlockData[self::CUSTOM_BLOCK_TEXTURE] ?? null;
        $blockGeometry = $customBlockData[self::CUSTOM_BLOCK_GEOMETRY] ?? null;

        $blockFunction = $this->getBlockFunction($blockName, $blockTexture, $blockGeometry);

        $block = CustomBlockRegistry::registerBlock(
            $blockFunction,
            $blockName,
            $blockIdentifier = $customBlockData[self::CUSTOM_BLOCK_IDENTIFIER]
        );

        $this->customBlockCache[$entry->getHash()] = $block;
        $this->registeredBlocks[] = $blockIdentifier;
    }

    /**
     * If the entity is not registered, it will be registered.
     *
     * @param CosmeticEntry[] $entries
     */
    public function registerEntries(array $entries): void
    {
        parent::registerEntries($entries);

        $cosmeticDataEntries = Utils::flattenArray(
            array_map(
                fn(CosmeticEntry $cosmetic): array => array_filter(
                    $cosmetic->getDataEntries(),
                    fn(CosmeticDataEntry $entry): bool => $this->isCustomBlockCosmetic($entry)
                ), $entries
            )
        );

        foreach ($cosmeticDataEntries as $cosmeticDataEntry) {
            if (in_array($this->getBlockIdentifier($cosmeticDataEntry), $this->registeredBlocks, true)) {
                continue;
            }

            $this->registerBlock($cosmeticDataEntry);
        }
    }

    protected function processBlock(CosmeticDataEntry $entry, Block $block): Block
    {
        return $block;
    }

    protected function isCustomBlockCosmetic(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::CUSTOM_BLOCK]);
    }
}