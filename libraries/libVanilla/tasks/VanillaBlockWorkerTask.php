<?php
declare(strict_types=1);

namespace libVanilla\tasks;

use libVanilla\features\MissingVanillaBlocks;
use libVanilla\utils\BlockRegistrationHelper;
use pocketmine\block\Block;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\utils\WoodType;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use ReflectionClass;

/**
 * Registers custom block serializers and deserializers on async worker threads.
 * This is CRITICAL for blocks to load correctly from chunks, since chunk loading happens on workers.
 */
class VanillaBlockWorkerTask extends AsyncTask
{
    private string $blockDataSerialized;

    public function __construct()
    {
        $data = [];
        foreach (MissingVanillaBlocks::getRegisteredBlocks() as $id => $block) {
            $extraData = [];

            if ($block instanceof \pocketmine\block\WeightedPressurePlate) {
                $extraData['weightedPressurePlate'] = true;
            } elseif ($block instanceof \pocketmine\block\FenceGate) {
                $extraData['fenceGate'] = true;
            } elseif ($block instanceof \pocketmine\block\Wood) {
                $extraData['wood'] = true;
                if ($block->isStripped()) {
                    $extraData['stripped'] = true;
                }
            }

            $data[$id] = [
                'typeId' => $block->getTypeId(),
                'class' => get_class($block),
                'name' => $block->getName(),
                'extra' => $extraData,
            ];
        }
        $this->blockDataSerialized = serialize($data);
    }

    public function onRun(): void
    {
        $data = unserialize($this->blockDataSerialized);
        $registry = RuntimeBlockStateRegistry::getInstance();
        $serializer = GlobalBlockStateHandlers::getSerializer();

        $refClass = new ReflectionClass(RuntimeBlockStateRegistry::class);
        $typeIndexProp = $refClass->getProperty("typeIndex");
        $typeIndex = $typeIndexProp->getValue($registry);

        foreach ($data as $blockId => $blockData) {
            $typeId = $blockData['typeId'];
            $blockClass = $blockData['class'];
            $blockName = $blockData['name'];
            $extra = $blockData['extra'] ?? [];

            $block = $this->createBlock($blockClass, $typeId, $blockName, $extra);
            if ($block === null) {
                continue;
            }

            // Register in runtime registry if not already present
            if (!isset($typeIndex[$typeId])) {
                $registry->register($block);
                // Refresh type index after registration
                $typeIndex = $typeIndexProp->getValue($registry);
            }

            // Skip if serializer is already registered
            if ($serializer->isRegistered($block)) {
                continue;
            }

            // Use the shared registrar-based mapping logic
            BlockRegistrationHelper::mapBlockSerializerDeserializer($block, $blockId);
        }
    }

    /**
     * Recreate a block instance from class name, type ID, and name.
     */
    /**
     * @param array<string, bool> $extra
     */
    private function createBlock(string $class, int $typeId, string $name, array $extra = []): ?Block
    {
        try {
            if (!class_exists($class)) {
                return null;
            }

            $identifier = new BlockIdentifier($typeId);
            $typeInfo = BlockRegistrationHelper::woodLikeInfo();

            if (isset($extra['fenceGate'])) {
                $block = new $class($identifier, $name, $typeInfo, WoodType::OAK);
            } elseif (isset($extra['wood'])) {
                $block = new $class($identifier, $name, $typeInfo, WoodType::OAK);
                if (isset($extra['stripped'])) {
                    $block->setStripped(true);
                }
            } else {
                $block = new $class($identifier, $name, $typeInfo);
            }

            return $block;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
