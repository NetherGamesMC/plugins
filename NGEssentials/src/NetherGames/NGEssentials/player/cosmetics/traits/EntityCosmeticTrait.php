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

use InvalidArgumentException;
use NetherGames\NGEssentials\entity\custom\CustomActorList;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\EntitySizeInfo;
use function array_diff;
use function array_filter;
use function array_map;
use function array_unique;
use function str_replace;
use function str_starts_with;

/**
 * @mixin Cosmetic
 */
trait EntityCosmeticTrait
{
    /** @var string[] */
    private array $entityIds = [];

    private const ENTITY_KEY = 'entity';
    private const ENTITY_RUNTIME_ID_KEY = 'runtime_id';
    private const ENTITY_ANIMATIONS_KEY = 'animations';

    private const ENTITY_HEIGHT_KEY = 'height';
    private const ENTITY_WIDTH_KEY = 'width';
    private const ENTITY_EYE_HEIGHT_KEY = 'eye_height';

    /**
     * If the entity is not registered, it will be registered.
     *
     * @param CosmeticEntry[] $entries
     */
    public function registerEntries(array $entries): void
    {
        parent::registerEntries($entries);

        $entityIds = array_filter(
            array_unique(Utils::flattenArray(array_map(
                fn(CosmeticEntry $cosmetic): array => array_map(
                    fn(CosmeticDataEntry $data): string => $this->getEntityId($data),
                    array_filter($cosmetic->getDataEntries(), fn(CosmeticDataEntry $entry): bool => $this->isEntityCosmeticEntry($entry))
                ),
                $entries
            ))),
            fn(string $entityId): bool => !str_starts_with($entityId, 'minecraft:')
        );

        sort($entityIds);

        $entityManager = $this->handler->getPlugin()->getEntityManager();
        foreach (array_diff($entityIds, $this->entityIds) as $entityId) {
            $entityManager->addCustomEntity(new CustomActorList(str_replace("ng:", '', $entityId), $entityId));
        }

        $this->entityIds = $entityIds;
    }

    protected function getEntityId(CosmeticDataEntry $entry): string
    {
        return $entry->data[self::ENTITY_KEY][self::ENTITY_RUNTIME_ID_KEY] ?? throw new InvalidArgumentException("Missing entity key for cosmetic $entry->id");
    }

    protected function getEntitySizeInfo(CosmeticDataEntry $entry): EntitySizeInfo
    {
        return new EntitySizeInfo(
            $entry->data[self::ENTITY_KEY][self::ENTITY_HEIGHT_KEY] ?? throw new InvalidArgumentException("Missing height key for cosmetic $entry->id"),
            $entry->data[self::ENTITY_KEY][self::ENTITY_WIDTH_KEY] ?? throw new InvalidArgumentException("Missing width key for cosmetic $entry->id"),
            $entry->data[self::ENTITY_KEY][self::ENTITY_EYE_HEIGHT_KEY] ?? null
        );
    }

    protected function getAnimation(CosmeticDataEntry $entry, string $key): ?string
    {
        return $entry->data[self::ENTITY_KEY][self::ENTITY_ANIMATIONS_KEY][$key] ?? null;
    }

    protected function isEntityCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::ENTITY_KEY]);
    }
}
