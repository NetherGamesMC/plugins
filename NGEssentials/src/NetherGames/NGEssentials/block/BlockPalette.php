<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block;

use NetherGames\NGEssentials\utils\TypeConverterSingletonTrait;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\BlockStateDictionaryEntry;
use pocketmine\network\mcpe\convert\BlockTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\AssumptionFailedError;
use ReflectionProperty;
use RuntimeException;
use function array_keys;
use function count;
use function hash;
use function strcmp;
use function usort;

final class BlockPalette
{
    use TypeConverterSingletonTrait;

    /** @var BlockStateDictionaryEntry[] */
    private array $states;
    /** @var BlockStateDictionaryEntry[] */
    private static array $customStates = [];

    private static bool $registered = false;

    private BlockTranslator $translator;
    private ReflectionProperty $bedrockKnownStates;
    private ReflectionProperty $stateDataToStateIdLookup;
    private ReflectionProperty $idMetaToStateIdLookupCache;
    private ReflectionProperty $fallbackStateId;

    public function __construct(TypeConverter $typeConverter)
    {
        $this->translator = $instance = $typeConverter->getBlockTranslator();
        $dictionary = $instance->getBlockStateDictionary();
        $this->states = $dictionary->getStates();

        $this->bedrockKnownStates = new ReflectionProperty($dictionary, "states");
        $this->stateDataToStateIdLookup = new ReflectionProperty($dictionary, "stateDataToStateIdLookup");
        $this->idMetaToStateIdLookupCache = new ReflectionProperty($dictionary, "idMetaToStateIdLookupCache");
        $this->fallbackStateId = new ReflectionProperty($instance, "fallbackStateId");

        $states = $this->getStatesByNames();

        foreach (self::$customStates as $state) {
            $states[$state->getStateName()][] = $state;
        }

        $this->sort($states);
    }

    /**
     * Inserts the provided state in to the correct position of the palette.
     */
    public static function insertState(CompoundTag $state, int $meta = 0): void
    {
        if (!self::$registered) {
            self::register();
        }

        if (($name = $state->getString(BlockStateData::TAG_NAME, "")) === "") {
            throw new RuntimeException("Block state must contain a StringTag called 'name'");
        }
        if (($properties = $state->getCompoundTag(BlockStateData::TAG_STATES)) === null) {
            throw new RuntimeException("Block state must contain a CompoundTag called 'states'");
        }

        $entry = new BlockStateDictionaryEntry($name, $properties->getValue(), $meta, null);

        foreach (BlockPalette::getAll() as $blockPalette) {
            $blockPalette->sortWith($entry);
        }

        self::$customStates[] = $entry;
    }

    private static function register(): void
    {
        foreach (TypeConverter::getAll() as $typeConverter) {
            self::getInstance($typeConverter);
        }

        TypeConverter::addCreationListener(static function (TypeConverter $converter): void {
            self::getInstance($converter);
        });
        self::$registered = true;
    }

    /**
     * @return array<string, BlockStateDictionaryEntry[]>
     */
    private function getStatesByNames(): array
    {
        $states = [];

        foreach ($this->states as $state) {
            $states[$state->getStateName()][] = $state;
        }

        return $states;
    }

    /**
     * Sorts the palette's block states in the correct order, also adding the provided state to the array.
     */
    private function sortWith(BlockStateDictionaryEntry $newState): void
    {
        $states = $this->getStatesByNames();

        // Append the new state we are sorting with at the end to preserve existing order.
        $states[$newState->getStateName()][] = $newState;

        $this->sort($states);
    }

    /**
     * @param array<string, BlockStateDictionaryEntry[]> $states
     */
    private function sort(array $states): void
    {
        $names = array_keys($states);
        // As of 1.18.30, blocks are sorted using a fnv164 hash of their names.
        usort($names, static fn(string $a, string $b) => strcmp(hash("fnv164", $a), hash("fnv164", $b)));
        $sortedStates = [];
        $stateId = 0;
        $stateDataToStateIdLookup = [];
        foreach ($names as $name) {
            // With the sorted list of names, we can now go back and add all the states for each block in the correct order.
            foreach ($states[$name] as $state) {
                $sortedStates[$stateId] = $state;
                if (count($states[$name]) === 1) {
                    $stateDataToStateIdLookup[$name] = $stateId;
                } else {
                    $stateDataToStateIdLookup[$name][$state->getRawStateProperties()] = $stateId;
                }
                $stateId++;
            }
        }
        $this->states = $sortedStates;
        $dictionary = $this->translator->getBlockStateDictionary();
        $this->bedrockKnownStates->setValue($dictionary, $sortedStates);
        $this->stateDataToStateIdLookup->setValue($dictionary, $stateDataToStateIdLookup);
        $this->idMetaToStateIdLookupCache->setValue($dictionary, null); //set this to null so pm can create a new cache
        $this->fallbackStateId->setValue($this->translator, $stateDataToStateIdLookup[BlockTypeNames::INFO_UPDATE] ??
            throw new AssumptionFailedError(BlockTypeNames::INFO_UPDATE . " should always exist")
        );
    }
}