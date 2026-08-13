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

namespace NetherGames\NGEssentials\entity;

use libReplay\session\record\RecordManager;
use NetherGames\NGEssentials\entity\custom\Custom;
use NetherGames\NGEssentials\entity\custom\CustomActorList;
use NetherGames\NGEssentials\entity\custom\CustomHuman;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\ServerData;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_map;
use function array_merge;
use function array_unshift;
use function base64_decode;
use function count;
use function str_replace;

class EntityManager extends BaseClass
{
    /** @var BossBar */
    private BossBar $bossBar;
    /** @var Custom[][] */
    private array $entities = [];
    /** @var CustomActorList[] */
    private array $customEntites = [];

    public function onEnable(): void
    {
        $this->getPlugin()->getServer()->getPluginManager()->registerEvents(new EntityListener($this), $this->getPlugin());

        $this->bossBar = new BossBar(NGEssentials::getLogo() . $this->getPlugin()->getServerManager()->getColor() . ServerManager::getName($this->getPlugin()->getServerManager()->getServerType()));

        /** @var EntityFactory $entityFactory */
        $entityFactory = EntityFactory::getInstance();
        $entityFactory->register(Arrow::class, function (World $world, CompoundTag $nbt): Arrow {
            return new Arrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
        }, ['Arrow', 'minecraft:arrow']);
        $entityFactory->register(Egg::class, function (World $world, CompoundTag $nbt): Egg {
            return new Egg(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['Egg', 'minecraft:egg']);
        $entityFactory->register(Snowball::class, function (World $world, CompoundTag $nbt): Snowball {
            return new Snowball(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['Snowball', 'minecraft:snowball']);

        $defaultWorld = $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();
        if ($this->getPlugin()->getServerManager()->getServerType() === ServerManager::FACTIONS) {
            $facKillerText = new FloatingText(new Location(84, 62, -17, $defaultWorld, 0.0, 0.0), '§l§aFACTIONS KILLS LEADERBOARD§r');
            $facStreakText = new FloatingText(new Location(95, 62, -17, $defaultWorld, 0.0, 0.0), '§l§aCURRENT STREAK LEADERBOARD§r');
            $facBestStreakText = new FloatingText(new Location(94, 62, -23, $defaultWorld, 0.0, 0.0), '§l§aBEST STREAK LEADERBOARD§r');

            $this->addEntity($facKillerText);
            $this->addEntity($facStreakText);
            $this->addEntity($facBestStreakText);

            $this->getPlugin()->getServerData()->setValue(ServerData::BOARDS, [
                0 => $facKillerText->getId(),
                1 => $facStreakText->getId(),
                2 => $facBestStreakText->getId()
            ]);
        }

        $this->addCustomEntity(new CustomActorList("MazePet", "ng:pet_maze"));
        $this->addCustomEntity(new CustomActorList("Snowbomb", "ng:meltdown_powerup_snowbomb"));
        $this->addCustomEntity(new CustomActorList("Slippery Boots", "ng:meltdown_powerup_slippery_boots"));
        $this->addCustomEntity(new CustomActorList("Ice Boots", "ng:meltdown_powerup_ice_boots"));
        $this->addCustomEntity(new CustomActorList("Landmine", "ng:bedwars_landmine"));

        $this->addCustomEntity(new CustomActorList("Starter Kit", "ng:skyblock_kit_starter"));
        $this->addCustomEntity(new CustomActorList("Ultra Kit", "ng:skyblock_kit_ultra"));
        $this->addCustomEntity(new CustomActorList("Emerald Kit", "ng:skyblock_kit_emerald"));
        $this->addCustomEntity(new CustomActorList("Legend Kit", "ng:skyblock_kit_legend"));
        $this->addCustomEntity(new CustomActorList("Blue Crate", "ng:skyblock_crate_blue"));
        $this->addCustomEntity(new CustomActorList("Green Crate", "ng:skyblock_crate_green"));
        $this->addCustomEntity(new CustomActorList("Red Crate", "ng:skyblock_crate_red"));
        $this->addCustomEntity(new CustomActorList("Purple Crate", "ng:skyblock_crate_purple"));
        $this->addCustomEntity(new CustomActorList("Associated Islands NPC", "ng:skyblock_npc_associated_islands"));
        $this->addCustomEntity(new CustomActorList("Public Islands NPC", "ng:skyblock_npc_public_islands"));
        $this->addCustomEntity(new CustomActorList("PvP NPC", "ng:skyblock_npc_pvp"));
        $this->addCustomEntity(new CustomActorList("Your Island NPC", "ng:skyblock_npc_your_island"));
        $this->addCustomEntity(new CustomActorList("Challenges NPC", "ng:skyblock_npc_challenges"));
        $this->addCustomEntity(new CustomActorList("Money Storage", "ng:mmo_npc_money_storage"));
    }

    public function addEntity(Custom $entity): void
    {
        $this->addEntities($entity->getLocation()->getWorld(), [$entity]);
    }

    public function addEntities(World $world, array $entities): void
    {
        foreach ($entities as $entity) {
            $this->entities[$world->getId()][$entity->getId()] = $entity;
        }

        $players = $world->getPlayers();
        if (($recordManager = RecordManager::getInstance()) !== null && ($recording = $recordManager->getRecording($world)) !== null) {
            $players[] = $recording->getCamera();
        }

        $this->spawnEntities($players, $entities);
    }

    /**
     * @param Player[] $players
     * @param Custom[] $entities
     */
    public function spawnEntities(array $players, array $entities): void
    {
        TypeConverter::broadcastByTypeConverter($players, function (TypeConverter $typeConverter) use ($entities): array {
            /** @var PlayerListEntry[] $entries */
            $entries = [];
            /** @var ClientboundPacket[] $packets */
            $packets = [];

            foreach ($entities as $entity) {
                if ($entity instanceof CustomHuman) {
                    $entries[] = $entity->getListEntry($typeConverter);
                }

                $packets[] = $entity->getSpawnPacket($typeConverter);
            }

            if (count($entries) > 0) {
                array_unshift($packets, PlayerListPacket::add($entries));
                $packets[] = PlayerListPacket::remove($entries);
            }

            return $packets;
        });
    }

    /**
     * @param CustomActorList $mob
     * @return void
     */
    public function addCustomEntity(CustomActorList $mob): void
    {
        $this->customEntites[] = new CustomActorList($mob->getName(), $mob->getId());
    }

    public function removeEntity(Custom $entity): void
    {
        $this->removeEntities($entity->getLocation()->getWorld(), [$entity]);
    }

    /**
     * @param Player[] $players
     * @param Custom[] $entities
     */
    public function despawnEntities(array $players, array $entities): void
    {
        $packets = [];

        foreach ($entities as $entity) {
            $packets[] = $entity->getDespawnPacket();
        }

        if (count($packets) > 0) {
            NetworkBroadcastUtils::broadcastPackets($players, $packets);
        }
    }

    public function getEntity(World $world, int $entityId): ?Custom
    {
        return $this->entities[$world->getId()][$entityId] ?? null;
    }


    /**
     * @param Custom[] $entities
     */
    public function removeEntities(World $world, ?array $entities = null): void
    {
        foreach ($entities ??= $this->getEntities($world) as $entity) {
            unset($this->entities[$world->getId()][$entity->getId()]);
        }

        $players = $world->getPlayers();
        if (($recordManager = RecordManager::getInstance()) !== null && ($recording = $recordManager->getRecording($world)) !== null) {
            $players[] = $recording->getCamera();
        }

        $this->despawnEntities($players, $entities);
    }

    /**
     * @param World|null $world
     * @return Custom[]
     */
    public function getEntities(?World $world = null): array
    {
        if ($world === null) {
            return array_merge(...$this->entities);
        }

        return $this->entities[$world->getId()] ?? [];
    }

    /**
     * @param Player[] $players
     * @param Custom[] $entities
     */
    public function sendMetadata(array $players, array $entities): void
    {
        TypeConverter::broadcastByTypeConverter($players, function (TypeConverter $typeConverter) use ($entities): array {
            return array_map(fn(Custom $entity) => $entity->getMetadataPacket($typeConverter), $entities);
        });
    }

    //TODO: redo this temporary api to register the entity.

    /**
     * @param AvailableActorIdentifiersPacket $packet
     * @return CacheableNbt
     */
    public function registerCustomEntity(AvailableActorIdentifiersPacket $packet): CacheableNbt
    {
        /** @var CompoundTag $root */
        $root = $packet->identifiers->getRoot();
        $newArray = $root->getListTag("idlist")->getValue();

        $rEntities = [];

        /** @var CompoundTag $entities */
        foreach ($newArray as $entities) {
            $rEntities[$entities->getString("id")] = true;
        }

        $rid = -128;
        foreach ($this->customEntites as $custom) {
            if (isset($rEntities[$custom->getId()])) {
                continue;
            }

            $newArray[] = CompoundTag::create()
                ->setString("bid", "")
                ->setByte("hasspawnegg", 1)
                ->setString("id", $custom->getId())
                ->setByte("rid", $rid)
                ->setByte("summable", 1);
            $rid++;
        }

        return new CacheableNbt(CompoundTag::create()->setTag("idlist", new ListTag($newArray)));
    }


    public function updateBossBar(): void
    {
        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();
        $serverData = $plugin->getServerData();
        $tournamentManager = $this->getPlugin()->getTournamentManager();

        $message = NGEssentials::getLogo() . $serverManager->getColor() . ServerManager::getName($serverManager->getServerType());
        $content = $serverData->getString(ServerData::BOSSBAR);
        $streaming = $serverData->getBool(ServerData::STREAM);
        $tournaments = $tournamentManager->getBossBarContent();
        $isWeekend = Utils::isWeekend();

        if ($content !== "" || $streaming || $tournaments !== "" || $isWeekend) {
            $message .= TextFormat::EOL . TextFormat::EOL;

            if ($streaming) {
                $message .= '§r§l§aLIVE STREAM ONLINE' . TextFormat::EOL . '§bJoin us now - ngmc.co/live';
            } elseif ($serverManager->getServerType() === ServerManager::LOBBY && $isWeekend) {
                $message .= '§r§l§aDOUBLE XP WEEKEND';
            } elseif ($tournaments !== "") {
                $message .= $tournaments;
            } else {
                $message .= $content;
            }
        }

        $bossbar = $this->getBossBar();
        if ($bossbar->getTitle() !== $message) {
            $bossbar->setTitle($message);
        }
    }

    public function getBossBar(): BossBar
    {
        return $this->bossBar;
    }
}