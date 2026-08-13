<?php

declare(strict_types=1);

namespace lobby\entity;

use libPhysX\PhysX;
use libVanilla\LibVanillaItems;
use lobby\entity\custom\BasicNPC;
use lobby\entity\custom\CrateEntity;
use lobby\entity\custom\GroundRangeEntity;
use lobby\entity\custom\IconMarker;
use lobby\entity\custom\RangeEntity;
use lobby\entity\custom\StaticItem;
use lobby\entity\minecraft\NPCDeserializer;
use lobby\features\npc\NPCCollection;
use lobby\utils\BaseTrait;
use lobby\utils\NPCUtils;
use NetherGames\NGEssentials\entity\custom\Custom;
use NetherGames\NGEssentials\entity\custom\CustomActorList;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\ServerData;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_map;
use function implode;
use function number_format;
use function ucfirst;

class EntityManager
{
    use BaseTrait;

    /** @var Entity[] */
    private array $entities = [];
    /** @var StaticItem */
    private StaticItem $elytraItem;

    public function __construct()
    {
        /** @var EntityFactory $entityFactory */
        $entityFactory = EntityFactory::getInstance();

        $entityFactory->register(StaticItem::class, function (World $world, CompoundTag $nbt): StaticItem {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            if ($itemTag === null) {
                throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            }

            $item = Item::nbtDeserialize($itemTag);
            if ($item->isNull()) {
                throw new SavedDataLoadingException("Item is invalid");
            }
            return new StaticItem(Helper::parseLocation($nbt, $world), $item, $nbt);
        }, ['StaticItem']);
        $entityFactory->register(RangeEntity::class, fn(World $world, CompoundTag $nbt): Entity => new RangeEntity(null, EntityDataHelper::parseLocation($nbt, $world), $nbt), ["RangeEntity"]);
        $entityFactory->register(GroundRangeEntity::class, fn(World $world, CompoundTag $nbt): Entity => new GroundRangeEntity(null, EntityDataHelper::parseLocation($nbt, $world), $nbt), ["GroundRangeEntity"]);
        $entityFactory->register(CrateEntity::class, fn(World $world, CompoundTag $nbt): Entity => new CrateEntity(EntityDataHelper::parseLocation($nbt, $world), null), ["CrateEntity"]);
        $entityFactory->register(BasicNPC::class, fn(World $world, CompoundTag $nbt): Entity => new BasicNPC("", "", Helper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt)), ["BasicNPC"]);
        $entityFactory->register(IconMarker::class, fn(World $world, CompoundTag $nbt): Entity => new IconMarker(EntityDataHelper::parseLocation($nbt, $world), $nbt->getString("tokenIdentifier"), $nbt->getByte("collectable") == 1, $nbt), ["BasicNPC"]);

        $entityManager = $this->getNGEssentials()->getEntityManager();
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($entityManager): void {
            $currentTime = microtime(true);
            /** @var HumanNPC[] $npcs */
            $npcs = array_filter($entityManager->getEntities(), static fn(Custom $entity) => $entity instanceof HumanNPC);
            /** @var NGPlayer[] $onlinePlayers */
            $onlinePlayers = $this->getPlugin()->getServer()->getOnlinePlayers();
            $players = array_filter($onlinePlayers, static fn(NGPlayer $player) => !$player->isInvisible() && ($currentTime - $player->getLastMoveTime()) <= 0.05);

            foreach ($players as $player) {
                $networkSession = $player->getNetworkSession();
                $eyePos = $player->getEyePos();
                $playerLocation = $player->getLocation();

                foreach ($npcs as $npc) {
                    $location = $npc->getLocation();

                    if (($location->distance($playerLocation) < 55)) {
                        $rotation = PhysX::calculateRotationEulerAngle($npc->getOffsetPosition($location), $eyePos);

                        $location->yaw = $rotation->yaw;
                        $location->pitch = $rotation->pitch;

                        $networkSession->sendDataPacket($npc->getMovePacket());
                    }
                }
            }
        }), 5);

        NPCUtils::spawnNPCs($entityManager);

        $defaultWorld = $this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld();
        $defaultWorld->setTime(12500);

        $this->elytraItem = new StaticItem(
            location: new Location(21, 25.5, 42.0, $defaultWorld, 0, 0),
            item: LibVanillaItems::ELYTRA()
        );

        $this->elytraItem->setNameTag("§3§lDaedulus Wings \n§3§l(§r§aCosmetics > Armor > Chestplate§3§l)");
        $this->elytraItem->setNameTagAlwaysVisible();

        $formatFn = fn(string $text) => TextFormat::BOLD . TextFormat::GREEN . $text . TextFormat::RESET;

        //$news1Text = new FloatingText(new Location(-12.5, 40, -16.5, $defaultWorld, 0.0, 0.0), $newsText);
        //$news2Text = new FloatingText(new Location(-20.5, 40, -8, $defaultWorld, 0.0, 0.0), $newsText);
        $creditText = new FloatingText(new Location(133.5, 45, -61.5, $defaultWorld, 0.0, 0.0), $formatFn("TOP CREDITS LEADERBOARD"));
        $winnerText = new FloatingText(new Location(127.5, 45, -56.5, $defaultWorld, 0.0, 0.0), $formatFn("GLOBAL WINS LEADERBOARD"));

        $bwWinnerText = new FloatingText(new Location(122.5, 45, -50.5, $defaultWorld, 0.0, 0.0), $formatFn("BEDWARS LEADERBOARD"));
        $duelsWinnerText = new FloatingText(new Location(131.5, 45, -40.5, $defaultWorld, 0.0, 0.0), $formatFn("DUELS LEADERBOARD"));
        $swWinnerText = new FloatingText(new Location(143.5, 45, -51.5, $defaultWorld, 0.0, 0.0), $formatFn("SKYWARS LEADERBOARD"));
        //$guildText = new FloatingText(new Location(103.5, 81, -92.5, $defaultWorld, 0.0, 0.0), '§l§aGUILDS LEADERBOARD§r');
        $tbWinnerText = new FloatingText(new Location(138.5, 45, -45.5, $defaultWorld, 0.0, 0.0), $formatFn("THE BRIDGE LEADERBOARD"));
        $tournamentLeaderboardText = new FloatingText(new Location(-9.5, 41, -5.5, $defaultWorld, 0.0, 0.0), $formatFn('TOURNAMENT LEADERBOARD'));
        $tournamentLeaderboardText->getMetadata()->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
        /*$onlineBoard = new HumanNPC($this->getPlugin(), new Location(88.5, 101, -5.5, $defaultWorld, 0.0, 0.0), $formatFn('Welcome to §eNether§6Games') . TextFormat::EOL . TextFormat::RED . 'nethergames.org' . TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::YELLOW . 'You are currently in ' . $this->getPlugin()->getServerManager()->getUniqueId() . TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::YELLOW . 'There are ' . TextFormat::GREEN . '%network_players%' . TextFormat::YELLOW . ' players across the network' . TextFormat::EOL . ' ' . TextFormat::EOL . TextFormat::AQUA . 'Hit an NPC to join a game!', new Skin('Standard_Custom', str_repeat("\x00", 8192)), null, null, function (string $name) {
            return str_replace('%network_players%', (string)$this->getPlugin()->getServerManager()->getOnlinePlayers(), $name);
        });*/

        NPCDeserializer::deserializeAndSpawn(NPCCollection::NPC_SPREAD_DATA, $defaultWorld);
        NPCDeserializer::deserializeAndSpawn(NPCCollection::NPC_MISC_LOCATIONS, $defaultWorld);

        //$entityManager->addEntity($news1Text);
        //$entityManager->addEntity($news2Text);
        $entityManager->addEntity($creditText);
        $entityManager->addEntity($winnerText);
        $entityManager->addEntity($bwWinnerText);
        $entityManager->addEntity($duelsWinnerText);
        $entityManager->addEntity($swWinnerText);
        $entityManager->addEntity($tbWinnerText);
        $entityManager->addEntity($tournamentLeaderboardText);
        //$entityManager->addEntity($guildText);
        //$entityManager->addEntity($onlineBoard);

        $this->getNGEssentials()->getServerData()->setValue(ServerData::BOARDS, [
            //0 => $news1Text->getId(),
            //1 => $news2Text->getId(),
            2 => $creditText->getId(),
            3 => $winnerText->getId(),
            4 => $bwWinnerText->getId(),
            5 => $duelsWinnerText->getId(),
            6 => $swWinnerText->getId(),
            7 => $tbWinnerText->getId(),
            /*7 => $guildText->getId(),
            8 => $onlineBoard->getId()*/
            9 => $tournamentLeaderboardText->getId()
        ]);

        $entityManager->addCustomEntity(new CustomActorList("crate", "ng:lobby_crate"));
        $entityManager->addCustomEntity(new CustomActorList("range", "ng:lobby_target_air"));
        $entityManager->addCustomEntity(new CustomActorList("groundRange", "ng:lobby_target_ground"));
        $entityManager->addCustomEntity(new CustomActorList("iconMarker", "ng:lobby_icon_marker"));
        $entityManager->addCustomEntity(new CustomActorList("npc", "minecraft:npc"));
    }

    /**
     * @return array
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function addEntity(Entity $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * @return StaticItem
     */
    public function getElytraItem(): StaticItem
    {
        return $this->elytraItem;
    }
}
