<?php

declare(strict_types=1);

namespace lobby\features\presents;

use JsonException;
use lobby\command\AddPresentCommand;
use lobby\command\CoordinateCommand;
use lobby\command\IconMarkerCommand;
use lobby\command\RangeCommand;
use lobby\features\FeaturesManager;
use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\entity\custom\CustomActorList;
use NetherGames\NGEssentials\entity\custom\EntityNPC;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\PopSound;
use pocketmine\world\World;
use function count;
use function in_array;
use function json_decode;
use function json_encode;
use function ksort;
use function random_int;
use function str_replace;
use function ucfirst;
use const JSON_THROW_ON_ERROR;

class Presents
{
    use BaseTrait;

    public const CHRISTMAS = "present";
    public const HALLOWEEN = "candy bundle";
    public const EASTER = "egg";

    public const CAPE_ID = 140;
    public const SEASON = self::EASTER;

    /** @var EntityNPC[] */
    private array $presents = [];
    /** @var array */
    private array $player_presents;

    public function __construct(private FeaturesManager $features)
    {
        $plugin = $features->getPlugin();
        /** @var World $defaultWorld */
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        if (self::HALLOWEEN === self::SEASON) {
            $defaultWorld->setTime(World::TIME_MIDNIGHT);
        } else {
            $defaultWorld->setTime(World::TIME_DAY);
        }
        $defaultWorld->stopTime();

        MySQLCredentials::executeSelect("presents.load", [], function (array $rows) use ($defaultWorld) {
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $id = $row["id"];
                    World::getBlockXYZ((int)$row["hash"], $x, $y, $z);

                    $this->presents[$id] = $this->getNPC($id, new Location($x + 0.5, $y, $z + 0.5, $defaultWorld, 0.0, 0.0));
                }

                ksort($this->presents);
            }
        });

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($defaultWorld): void {
            $presentCount = count($this->getPresents());
            $preString = TextFormat::GOLD . ucfirst(self::SEASON) . " Hunt" . TextFormat::YELLOW . ": " . TextFormat::GREEN;

            foreach ($defaultWorld->getPlayers() as $player) {
                if (($presents = $this->getPlayerPresents($player)) !== null) {
                    $count = count($presents);

                    if ($count < $presentCount) {
                        $player->sendJukeboxPopup($preString . $count . TextFormat::YELLOW . "/" . TextFormat::GREEN . $presentCount);
                    }
                }
            }
        }), 20);

        if (NGEssentials::isInDevelopmentMode()) {
            $plugin->getServer()->getCommandMap()->register(AddPresentCommand::class, new AddPresentCommand($this->getFeatures()));
            $plugin->getServer()->getCommandMap()->register(RangeCommand::class, new RangeCommand());
            $plugin->getServer()->getCommandMap()->register(IconMarkerCommand::class, new IconMarkerCommand());
        }

        $plugin->getServer()->getCommandMap()->register(CoordinateCommand::class, new CoordinateCommand());

        foreach ($this->getRuntimeIds() as $runtimeId) {
            $this->getNGEssentials()->getEntityManager()->addCustomEntity(new CustomActorList(str_replace($runtimeId, "ng:", ""), $runtimeId));
        }

        foreach ($defaultWorld->getLoadedChunks() as $chunk) {
            $this->setCorrectBiome($chunk);
        }

        switch (self::SEASON) {
            case self::HALLOWEEN:
                $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($defaultWorld): void {
                    $x = random_int(-51, 234);
                    $z = random_int(-167, 96);
                    $y = $defaultWorld->getHighestBlockAt($x, $z);

                    if ($y !== null) {
                        $pk = AddActorPacket::create(
                            $actorId = Entity::nextRuntimeId(),
                            $actorId,
                            EntityIds::LIGHTNING_BOLT,
                            new Vector3($x, $y, $z),
                            null,
                            0,
                            0,
                            0,
                            0,
                            [],
                            [],
                            new PropertySyncData([], []),
                            []
                        );

                        foreach ($this->getNGEssentials()->getPlayerManager()->unsetFPSPlayers($defaultWorld->getPlayers()) as $player) {
                            /** @var NGPlayer $player */
                            $player->getNetworkSession()->sendDataPacket($pk);
                            $player->playSound("ambient.weather.thunder");
                        }
                    }
                }), 20 * 20);
                break;
            case self::CHRISTMAS:
                //$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($defaultWorld): void {
                //    for ($i = 0; $i <= 10; $i++) {
                //        $colors = DyeColor::cases();
                //        $types = FireworkRocketType::cases();
                //
                //        $color = $colors[array_rand($colors)];
                //        $type = $types[array_rand($types)];
                //
                //        $x = 75 + mt_rand(-15, 15);
                //        $z = -22 + mt_rand(-15, 15);
                //        $y = $defaultWorld->getHighestBlockAt($x, $z);
                //        $firework = new FireworkEntity(new Location($x, $y, $z, $defaultWorld, 0.0, 0.0), 20 + mt_rand(0, 12), [
                //            new FireworkRocketExplosion($type, [$color], [], false, false)
                //        ]);
                //
                //        foreach ($this->getNGEssentials()->getPlayerManager()->unsetFPSPlayers($defaultWorld->getPlayers()) as $player) {
                //            $firework->spawnTo($player);
                //        }
                //    }
                //}), 20);
                break;
        }
    }

    /**
     * @return string[]
     */
    public function getRuntimeIds(): array
    {
        return match (self::SEASON) {
            self::CHRISTMAS => [
                "ng:lobby_hunt_present_1_blue",
                "ng:lobby_hunt_present_1_frost",
                "ng:lobby_hunt_present_1_green",
                "ng:lobby_hunt_present_1_grey",
                "ng:lobby_hunt_present_1_purple",
                "ng:lobby_hunt_present_2_blue",
                "ng:lobby_hunt_present_2_cyan",
                "ng:lobby_hunt_present_2_green",
                "ng:lobby_hunt_present_2_red",
                "ng:lobby_hunt_present_3_green",
                "ng:lobby_hunt_present_3_pink",
                "ng:lobby_hunt_present_3_purple",
                "ng:lobby_hunt_present_3_yellow",
            ],
            self::EASTER => [
                "ng:lobby_hunt_egg_variant_1",
                "ng:lobby_hunt_egg_variant_2",
                "ng:lobby_hunt_egg_variant_3",
                "ng:lobby_hunt_egg_variant_4",
                "ng:lobby_hunt_egg_variant_5",
                "ng:lobby_hunt_egg_variant_6",
                "ng:lobby_hunt_egg_variant_7",
                "ng:lobby_hunt_egg_variant_8",
            ],
            self::HALLOWEEN => [
                "ng:lobby_hunt_candy_bundle",
            ]
        };
    }

    public function setCorrectBiome(Chunk $chunk): void
    {
        return;
    }

    private function getNPC(int $id, Location $location): EntityNPC
    {
        $onClick = function (Player $player) use ($id) {
            $this->foundPresent($player, $id);
        };

        $runtimeIds = $this->getRuntimeIds();
        $npc = new EntityNPC($location, TextFormat::GRAY . ucfirst(self::SEASON), $runtimeIds[$id % count($runtimeIds)], $onClick, null);

        $npc->getMetadata()->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 0);

        return $npc;
    }

    public function foundPresent(Player $player, int $id): void
    {
        if ($this->hasFound($player, $id)) {
            $player->getWorld()->addSound($player->getPosition(), new AnvilFallSound(), [$player]);
            $player->sendMessage(TextFormat::RED . "You have already found this " . self::SEASON . "!");
        } else {
            $plugin = $this->getNGEssentials();
            $npc = $this->getPresents()[$id];

            NetworkBroadcastUtils::broadcastPackets($npc->getLocation()->getWorld()->getViewersForPosition($npc->getLocation()), [
                AnimateEntityPacket::create("animation.ng.lobby.hunt.present.open", "", "", 0, "", 0, [$npc->getId()])
            ]);

            $this->getNGEssentials()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($npc, $player): void {
                if ($player->isConnected()) {
                    $npc->despawn($player);
                }
            }), 20);

            $this->player_presents[$player->getId()][] = $id;

            $player->getWorld()->addSound($player->getPosition(), new PopSound(), [$player]);

            if (($count = $this->getPresentsFound($player)) === ($totalCount = count($this->getPresents()))) {
                $player->sendMessage(TextFormat::GREEN . "Congratulations! You found all the " . self::SEASON . "s!");

                $cosmetic = CosmeticHandler::CAPES();
                $cosmetic->give($player, $entry = $cosmetic->getEntry(self::CAPE_ID));

                $player->sendMessage(TextFormat::GREEN . "You've received your " . $entry->name . " cape!");
            } else {
                $player->sendMessage(TextFormat::GREEN . "You found a " . self::SEASON . "!" . TextFormat::GRAY . " ($count/$totalCount)");
                $player->sendMessage(TextFormat::GREEN . "You have " . ($remaining = ($totalCount - $count)) . " more " . self::SEASON . ($remaining === 1 ? "" : "s") . " to collect!");
                $player->sendMessage(TextFormat::YELLOW . "Find all the " . self::SEASON . "s to get a reward!");

                $xp = $plugin->getPlayerData()->addInt($player, PlayerData::XP, 10, true);
                $plugin->getPlayerManager()->setStatsBar($player);

                $plugin->getServerData()->getScoreBoard()->setLine([$player], 8, CustomIcon::EXPERIENCE . "Level: " . $plugin->getPlayerManager()->getLevelFormat((int)ExperienceUtils::getLevelFromXp($xp)));
                $player->sendMessage(TextFormat::YELLOW . "+10 XP");
            }

            $this->savePresents($player);
        }
    }

    public function hasFound(Player $player, int $id): bool
    {
        return in_array($id, $this->getPlayerPresents($player) ?? [], true);
    }

    /**
     * @return int[]|null
     */
    public function getPlayerPresents(Player $player): ?array
    {
        return $this->player_presents[$player->getId()] ?? null;
    }

    /**
     * @return EntityNPC[]
     */
    public function getPresents(): array
    {
        return $this->presents;
    }

    public function getPresentsFound(Player $player): int
    {
        return count($this->player_presents[$player->getId()] ?? []);
    }

    public function savePresents(Player $player, bool $unset = false): void
    {
        if (isset($this->player_presents[$player->getId()])) {
            try {
                MySQLCredentials::executeInsert("player_presents.save", ["xuid" => $player->getXuid(), "presents" => json_encode($this->player_presents[$player->getId()], JSON_THROW_ON_ERROR)]);
            } catch (JsonException) {

            }
        }

        if ($unset) {
            unset($this->player_presents[$player->getId()]);
        }
    }

    public function getFeatures(): FeaturesManager
    {
        return $this->features;
    }

    public function addPresent(int $id, Vector3 $vector3): void
    {
        $plugin = $this->getFeatures()->getPlugin();

        /** @var World $defaultWorld */
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();
        $location = new Location(($floorX = $vector3->getFloorX()) + 0.5, ($floorY = $vector3->getFloorY()), ($floorZ = $vector3->getFloorZ()) + 0.5, $defaultWorld, 0.0, 0.0);

        $this->presents[$id] = $npc = $this->getNPC($id, $location);

        ksort($this->presents);

        foreach ($defaultWorld->getPlayers() as $player) {
            $npc->spawn($player);
        }

        MySQLCredentials::executeInsert("presents.add", ["id" => $id, "hash" => World::blockHash($floorX, $floorY, $floorZ)]);
    }

    public function loadPresents(Player $player): void
    {
        MySQLCredentials::executeSelect("player_presents.load", ["xuid" => $player->getXuid()], function (array $rows) use ($player) {
            if ($player->isConnected()) {
                $this->player_presents[$player->getId()] = count($rows) > 0 ? json_decode($rows[0]["presents"], true, 512, JSON_THROW_ON_ERROR) : [];

                $presents = $this->getPresents();

                if ($this->getPresentsFound($player) < count($presents)) {
                    foreach ($presents as $id => $present) {
                        if (!$this->hasFound($player, $id)) {
                            $present->spawn($player);
                        }
                    }
                } else {
                    $cosmetic = CosmeticHandler::CAPES();
                    $cosmetic->give($player, $cosmetic->getEntry(self::CAPE_ID));
                }
            }
        });
    }

    public function sendWeather(Player $player, bool $remove = false): void
    {
        $networkSession = $player->getNetworkSession();
        switch (self::SEASON) {
            case self::CHRISTMAS:
                //$networkSession->sendDataPacket(LevelEventPacket::create($remove ? LevelEvent::STOP_RAIN : LevelEvent::START_RAIN, 2500, null));
                break;
            case self::HALLOWEEN:
                $networkSession->sendDataPacket(LevelEventPacket::create($remove ? LevelEvent::STOP_RAIN : LevelEvent::START_RAIN, 10000, null));
                $networkSession->sendDataPacket(LevelEventPacket::create($remove ? LevelEvent::STOP_THUNDER : LevelEvent::START_THUNDER, 10000, null));
                break;
        }
    }
}
