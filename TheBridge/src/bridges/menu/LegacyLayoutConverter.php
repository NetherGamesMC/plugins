<?php

namespace bridges\menu;

use GlobalLogger;
use function array_key_first;
use function count;
use function is_array;

class LegacyLayoutConverter
{
    public const IRON_SWORD = 267;
    public const BOW = 261;
    public const DIAMOND_PICKAXE = 278;
    public const TERRACOTTA = 159;
    public const GOLDEN_APPLE = 322;
    public const ARROW = 262;

    public static function convert(array $data): ?array
    {
        $results = [];

        foreach ($data as $preferredIndex => [$itemId,]) {
            // these sadly will have to be hardcoded in PM5
            $defaultIndex = match ($itemId) {
                self::IRON_SWORD => LayoutEditor::SWORD_INDEX,
                self::BOW => LayoutEditor::BOW_INDEX,
                self::DIAMOND_PICKAXE => LayoutEditor::PICKAXE_INDEX,
                self::TERRACOTTA => isset($results[LayoutEditor::TERRACOTTA_INDEX_1]) ? LayoutEditor::TERRACOTTA_INDEX_2 : LayoutEditor::TERRACOTTA_INDEX_1,
                self::GOLDEN_APPLE => LayoutEditor::GOLDEN_APPLE_INDEX,
                self::ARROW => LayoutEditor::ARROW_INDEX,
                default => null,
            };

            if ($defaultIndex === null) {
                GlobalLogger::get()->alert("Unhandled inventory transaction with id: " . $itemId);
            } elseif (!isset($results[$defaultIndex])) {
                $results[$defaultIndex] = $preferredIndex;
            }
        }

        return count($results) === LayoutEditor::AMOUNT ? $results : null;
    }

    public static function isLegacyFormat(array $data): bool
    {
        return ($dataCount = count($data)) !== 0 && ($dataCount > LayoutEditor::AMOUNT || is_array($data[array_key_first($data)]));
    }
}