<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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

namespace libVanilla;

use BadMethodCallException;
use libVanilla\features\Feature;
use libVanilla\item\ChestMinecart;
use libVanilla\item\Crossbow;
use libVanilla\item\ElytraItem;
use libVanilla\item\EnderEye;
use libVanilla\item\Fireball;
use libVanilla\item\FishingRod;
use libVanilla\item\NameTag;
use libVanilla\item\Shield as ShieldItem;
use libVanilla\item\Trident;
use pmmp\thread\Thread;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use function array_filter;
use function get_class;

/**
 * @method static ChestMinecart CHEST_MINECART()
 * @method static NameTag NAME_TAG()
 * @method static EnderEye ENDER_EYE()
 * @method static Crossbow CROSSBOW()
 * @method static ElytraItem ELYTRA()
 * @method static Fireball FIREBALL()
 * @method static FishingRod FISHING_ROD()
 * @method static ShieldItem SHIELD()
 * @method static Trident TRIDENT()
 */
final class LibVanillaItems
{
    use CloningRegistryTrait;

    protected static function setup(): void
    {
        self::register(ItemTypeNames::CHEST_MINECART, VanillaPlugin::CHEST_MINECART(), new ChestMinecart(new ItemIdentifier(ItemTypeIds::newId()), "Chest with Minecart"), ["chest_minecart"]);
        self::register(ItemTypeNames::NAME_TAG, VanillaPlugin::NAME_TAG(), new NameTag(new ItemIdentifier(ItemTypeIds::newId()), 'Name Tag'), ["name_tag"]);
        self::register(ItemTypeNames::ENDER_EYE, VanillaPlugin::ENDER_EYE(), new EnderEye(new ItemIdentifier(ItemTypeIds::newId()), 'Ender Eye'), ["ender_eye"]);
        self::register(ItemTypeNames::CROSSBOW, VanillaPlugin::CROSSBOW(), new Crossbow(new ItemIdentifier(ItemTypeIds::CROSSBOW), "Crossbow"), ["crossbow"]);
        self::register(ItemTypeNames::ELYTRA, VanillaPlugin::ELYTRA(), new ElytraItem(new ItemIdentifier(ItemTypeIds::ELYTRA), "Elytra"), ["elytra"]);
        self::register(ItemTypeNames::FIRE_CHARGE, VanillaPlugin::FIREBALL(), new Fireball(new ItemIdentifier(ItemTypeIds::FIRE_CHARGE), "Fireball"), ["fireball"]);
        self::register(ItemTypeNames::FISHING_ROD, VanillaPlugin::FISHING_ROD(), new FishingRod(new ItemIdentifier(ItemTypeIds::FISHING_ROD), "Fishing Rod"), ["fishing_rod"]);
        self::register(ItemTypeNames::SHIELD, VanillaPlugin::SHIELD(), new ShieldItem(new ItemIdentifier(ItemTypeIds::SHIELD), "Shield"), ["shield"]);
        self::register(ItemTypeNames::TRIDENT, VanillaPlugin::TRIDENTS(), new Trident(new ItemIdentifier(ItemTypeIds::TRIDENT), "Trident"), ["trident"]);
    }

    /**
     * @param string $autoloader Registering plugin Composer Autoloader path
     */
    public static function crossThreadRegister(PluginBase $plugin, string $autoloader): void
    {
        if (Thread::getCurrentThread() !== null) {
            return; // we can only run this code at the main thread
        }
        $plugin->getScheduler()->scheduleTask(new ClosureTask(function () use ($plugin, $autoloader): void {
            $pool = $plugin->getServer()->getAsyncPool();
            $pool->addWorkerStartHook(function (int $workerId) use ($pool, $autoloader): void {
                self::checkInit();
                $enabledFeatures = array_map(
                    callback: fn(LibVanillaItems $member) => get_class($member->feature()),
                    array: array_filter(
                        array: self::$members,
                        callback: fn(LibVanillaItems $member) => $member->feature()->isRegistered()
                    )
                );

                $pool->submitTaskToWorker(new class($autoloader, serialize($enabledFeatures)) extends AsyncTask {
                    public function __construct(
                        private string $autoloader,
                        private string $serializedEnabledFeatures
                    )
                    {
                    }

                    public function onRun(): void
                    {
                        require_once $this->autoloader;
                        LibVanillaItems::workerRegister(unserialize($this->serializedEnabledFeatures));
                    }
                }, $workerId);
            });
        }));
    }

    /**
     * @param string[] $names
     */
    protected static function register(string $id, Feature $associatedFeature, Item $item, array $names): void
    {
        // un-prefix since `ItemTypeNames::*` is prefixed by "minecraft:"
        $registeredId = substr($id, strpos($id, ":") + 1); // minecraft:item_name => item_name
        self::_registryRegister($names[0] ?? $registeredId, new self($id, $associatedFeature, $item, $names));
    }

    /**
     * Loads the registry members if they don't already exist
     */
    public static function ensureInitialization(): void
    {
        self::checkInit();
        /** @var array<LibVanillaItems> $filteredMembers */
        $filteredMembers = array_filter(
            array: self::$members,
            callback: fn(LibVanillaItems $member) => $member->feature()->isRegistered()
        );
        foreach ($filteredMembers as $member) {
            $member->ensureRegistration();
        }
    }

    /**
     * @param string[] $features
     *
     * @internal This is only for async worker pool registration
     */
    public static function workerRegister(array $features): void
    {
        self::checkInit();
        /** @var array<LibVanillaItems> $filteredMembers */
        $filteredMembers = array_filter(
            array: self::$members,
            callback: fn(LibVanillaItems $member) => in_array(get_class($member->feature()), $features)
        );
        foreach ($filteredMembers as $member) {
            $member->ensureRegistration();
        }
    }

    /**
     * Throws an exception if the associated feature is not registered before accessing
     */
    protected static function preprocessMember(self $member): Item
    {
        if (!$member->feature()->isRegistered()) {
            throw new BadMethodCallException(get_class($member->feature()) . " must be registered before the items can be used");
        }

        return $member->item();
    }

    protected bool $isRegistered = false;

    /**
     * @param string[] $names
     */
    public function __construct(protected string $id, protected Feature $associatedFeature, protected Item $item, protected array $names)
    {
    }

    /**
     * Gets the feature associated with the item
     */
    public function feature(): Feature
    {
        return $this->associatedFeature;
    }

    /**
     * Returns a cloned instance of the item
     */
    public function item(): Item
    {
        $this->ensureRegistration();

        return clone $this->item;
    }

    /**
     * Registers the item to the item factory if it isn't already
     */
    private function ensureRegistration(): void
    {
        if ($this->isRegistered) {
            return;
        }

        // this entire thing is a hack
        $deserializer = GlobalItemDataHandlers::getDeserializer();
        $serializer = GlobalItemDataHandlers::getSerializer();

        $deserializerRefClass = new ReflectionClass(ItemDeserializer::class);
        $deserializerRefProp = $deserializerRefClass->getProperty("deserializers");

        $serializerRefClass = new ReflectionClass(ItemSerializer::class);
        $serializerItemRefProp = $serializerRefClass->getProperty("itemSerializers");

        $deserializerMap = $deserializerRefProp->getValue($deserializer);
        unset($deserializerMap[$this->id]);
        $deserializerRefProp->setValue($deserializer, $deserializerMap);

        $itemSerializerMap = $serializerItemRefProp->getValue($serializer);
        unset($itemSerializerMap[$this->item->getTypeId()]);
        $serializerItemRefProp->setValue($serializer, $itemSerializerMap);
        // ===========================

        $deserializer->map($this->id, fn() => clone $this->item);
        $serializer->map($this->item, fn() => new SavedItemData($this->id));

        foreach ($this->names as $name) {
            StringToItemParser::getInstance()->override($name, fn() => clone $this->item);
        }

        $this->isRegistered = true;
    }
}