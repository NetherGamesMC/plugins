<?php

declare(strict_types=1);

namespace lobby\utils;

use NetherGames\NGEssentials\entity\custom\CustomActorList;
use NetherGames\NGEssentials\entity\custom\EntityNPC;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\entity\EntityManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\servers\Cluster;
use NetherGames\NGEssentials\servers\Server;
use NetherGames\NGEssentials\utils\SkinUtils;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\WorldManager;
use Symfony\Component\Filesystem\Path;
use function in_array;
use function str_replace;
use function strtolower;

class NPCUtils
{
    /**
     * Private constants that are used in most of the NPC names
     */
    private const PLAYER_COUNT_VARIABLE = "%PLAYER_COUNT%";
    private const TAP_TO_CONNECT = TextFormat::AQUA . "Tap to connect";

    public const LOCATION = 0;
    public const NAME = 1;
    public const COMMAND = 2;
    public const ENTITY_ID = 3;
    public const SCALE = 4;
    public const MESSAGE = 5;
    public const SKIN = 6;

    public const DIALOG_TITLE = 0;
    public const DIALOG_DESCRIPTION = 2;

    public const LOCATION_X = 0;
    public const LOCATION_Y = 1;
    public const LOCATION_Z = 2;
    public const LOCATION_YAW = 3;
    public const LOCATION_WORLD = 4;

    private static function getNPCs(): array
    {
        return [
            ServerManager::LOBBY => [
                'lobby_spellbook' => [
                    self::LOCATION => [
                        self::LOCATION_X => -21.5,
                        self::LOCATION_Y => 39,
                        self::LOCATION_Z => -3.5,
                        self::LOCATION_YAW => 315,
                    ],
                    self::SCALE => 1.0,
                    self::NAME => '',
                    self::COMMAND => 'stats',
                    self::ENTITY_ID => 'ng:lobby_spellbook'
                ],
                'store_npc' => [
                    self::LOCATION => [
                        self::LOCATION_X => -6.5,
                        self::LOCATION_Y => 39,
                        self::LOCATION_Z => -18.5,
                        self::LOCATION_YAW => 315,
                    ],
                    self::SCALE => 1.0,
                    self::NAME => '',
                    self::ENTITY_ID => 'ng:lobby_npc_store',
                    self::MESSAGE => "§6§lNetherGames§r is ran by the purchases of the community. \nGet amazing perks, cosmetics and more on §3store.nethergames.org §rto support the server. \nWe are thankful for every purchase."
                ],
            ],
        ];
    }

    public static function getServerTypeNPCs(): array
    {
        return [
            ServerManager::LOBBY => [
                ServerManager::SW => [
                    self::LOCATION => [
                        self::LOCATION_X => 16,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => -1,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Skywars" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "sw",
                    self::ENTITY_ID => "ng:lobby_npc_skywars",
                    self::SCALE => 0.4
                ],
                ServerManager::BW => [
                    self::LOCATION => [
                        self::LOCATION_X => 15,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 7,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Bedwars" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "bw",
                    self::ENTITY_ID => "ng:lobby_npc_bedwars",
                    self::SCALE => 0.6
                ],
                ServerManager::MM => [
                    self::LOCATION => [
                        self::LOCATION_X => 15,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => -5,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Murder Mystery" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "mm",
                    self::ENTITY_ID => "ng:lobby_npc_murder_mystery"
                ],
                TextFormat::EOL . "soon" => [
                    self::LOCATION => [
                        self::LOCATION_X => 5,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => -14,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Coming Soon",
                    self::ENTITY_ID => "ng:lobby_npc_coming_soon",
                    self::SCALE => 0.5
                ],
                TextFormat::EOL . TextFormat::EOL . "soon" => [
                    self::LOCATION => [
                        self::LOCATION_X => -18,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 9,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Coming Soon",
                    self::ENTITY_ID => "ng:lobby_npc_coming_soon",
                    self::SCALE => 0.5
                ],
                ServerManager::CQ => [
                    self::LOCATION => [
                        self::LOCATION_X => 16,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 3,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Conquests" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "cq",
                    self::ENTITY_ID => "ng:lobby_npc_conquests",
                    self::SCALE => 0.6
                ],
                ServerManager::DUELS => [
                    self::LOCATION => [
                        self::LOCATION_X => 13,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => -9,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Duels" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "duels",
                    self::ENTITY_ID => "ng:lobby_npc_duels"
                ],
                ServerManager::CREATIVE => [
                    self::LOCATION => [
                        self::LOCATION_X => -9,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 19,
                        self::LOCATION_YAW => 180,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Creative" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "creative",
                    self::ENTITY_ID => "ng:lobby_npc_creative",
                    self::SCALE => 0.65
                ],
                ServerManager::FACTIONS => [
                    self::LOCATION => [
                        self::LOCATION_X => -1,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 20,
                    ],
                    self::NAME => TextFormat::BOLD . TextFormat::RED . 'SEASON 10' . TextFormat::EOL . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . "Factions" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "factions",
                    self::ENTITY_ID => "ng:lobby_npc_factions"
                ],
                ServerManager::UHC => [
                    self::LOCATION => [
                        self::LOCATION_X => -5,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 20,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "UHC" . TextFormat::EOL . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "uhc",
                    self::ENTITY_ID => "ng:lobby_npc_uhc"
                ],
                ServerManager::SB => [
                    self::LOCATION => [
                        self::LOCATION_X => 3,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 19,
                    ],
                    self::NAME => TextFormat::BOLD . TextFormat::RED . 'SEASON 4' . TextFormat::EOL . TextFormat::EOL . TextFormat::YELLOW . TextFormat::BOLD . "Skyblock" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT, self::COMMAND => "sb",
                    self::ENTITY_ID => "ng:lobby_npc_skyblock",
                    self::SCALE => 0.4
                ],
                ServerManager::TB => [
                    self::LOCATION => [
                        self::LOCATION_X => -13,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 17,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "The Bridge" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "tb",
                    self::ENTITY_ID => "ng:lobby_npc_the_bridge"
                ],
                ServerManager::AC => [
                    self::LOCATION => [
                        self::LOCATION_X => -16,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => 13,
                        self::LOCATION_YAW => 180,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Arcade" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "ac",
                    self::ENTITY_ID => "ng:lobby_npc_arcade"
                ],
                ServerManager::SG => [
                    self::LOCATION => [
                        self::LOCATION_X => 9,
                        self::LOCATION_Y => 41,
                        self::LOCATION_Z => -12,
                    ],
                    self::NAME => TextFormat::YELLOW . TextFormat::BOLD . "Survival Games" . TextFormat::EOL . self::PLAYER_COUNT_VARIABLE . TextFormat::EOL . self::TAP_TO_CONNECT,
                    self::COMMAND => "sg",
                    self::ENTITY_ID => "ng:lobby_npc_survival_games"
                ],
                'soon' => [
                    self::LOCATION => [
                        self::LOCATION_X => 168.5,
                        self::LOCATION_Y => 79,
                        self::LOCATION_Z => 1426.5,
                        self::LOCATION_WORLD => 'ArcadeLobby'
                    ],
                    self::NAME => '§e§lComing Soon',
                    self::ENTITY_ID => "ng:lobby_npc_coming_soon",
                    self::SCALE => 0.5
                ],
                //ServerManager::SP => [
                //    self::LOCATION => [
                //        self::LOCATION_X => 168.5,
                //        self::LOCATION_Y => 79,
                //        self::LOCATION_Z => 1426.5,
                //        self::LOCATION_WORLD => 'ArcadeLobby'
                //    ],
                //    self::NAME => '§l§aNEW MODE' . TextFormat::EOL . '§e§lSpleef' . TextFormat::EOL . '%PLAYER_COUNT%' . TextFormat::EOL . '§bTap to connect',
                //    self::COMMAND => 'sp',
                //],
                ServerManager::MS => [
                    self::LOCATION => [
                        self::LOCATION_X => 178.5,
                        self::LOCATION_Y => 79,
                        self::LOCATION_Z => 1425.5,
                        self::LOCATION_WORLD => 'ArcadeLobby'
                    ],
                    self::NAME => '§e§lMomma Says' . TextFormat::EOL . '%PLAYER_COUNT%' . TextFormat::EOL . '§bTap to connect',
                    self::COMMAND => 'ms',
                ],
                ServerManager::SC => [
                    self::LOCATION => [
                        self::LOCATION_X => 173.5,
                        self::LOCATION_Y => 79,
                        self::LOCATION_Z => 1425.5,
                        self::LOCATION_WORLD => 'ArcadeLobby'
                    ],
                    self::NAME => '§e§lSoccer' . TextFormat::EOL . '%PLAYER_COUNT%' . TextFormat::EOL . '§bTap to connect',
                    self::COMMAND => 'sc',
                ]
            ],
        ];
    }

    private static function parseLocation(WorldManager $worldManager, mixed $values): Location
    {
        $location = $values[NPCUtils::LOCATION];

        if (isset($location[NPCUtils::LOCATION_WORLD])) {
            $world = $worldManager->getWorldByName($location[NPCUtils::LOCATION_WORLD]);
        } else {
            $world = $worldManager->getDefaultWorld();
        }

        return new Location(
            $location[NPCUtils::LOCATION_X],
            $location[NPCUtils::LOCATION_Y] - 0.02,
            $location[NPCUtils::LOCATION_Z],
            $world,
            $location[NPCUtils::LOCATION_YAW] ?? 90,
            0
        );
    }

    private static function getCallable(array $values): ?callable
    {
        if (isset($values[NPCUtils::COMMAND])) {
            return static function (Player $player) use ($values) {
                $player->getServer()->dispatchCommand($player, $values[NPCUtils::COMMAND]);
            };
        } elseif (isset($values[NPCUtils::MESSAGE])) {
            return static function (Player $player) use ($values) {
                $player->sendMessage($values[NPCUtils::MESSAGE]);
            };
        } else {
            return null;
        }
    }

    private static function getNameCallable(NGEssentials $ess, string $npcName): callable
    {
        return function (string $name) use ($ess, $npcName) {
            $status = [
                Server::OFFLINE => 0,
                Server::ONLINE => 0,
                Server::FULL => 0,
            ];

            $onlinePlayers = 0;
            $maxPlayers = 0;

            $addCluster = function (Cluster $cluster) use (&$onlinePlayers, &$maxPlayers, &$status) {
                $onlinePlayers += $cluster->getOnlinePlayers();
                $maxPlayers += $cluster->getMaxPlayers();

                $status[$cluster->getStatus()]++;
            };

            $serverManager = $ess->getServerManager();
            $addServerType = function (string $serverType) use ($addCluster, $serverManager) {
                $server = ServerManager::BUTTONS[ServerManager::getName($serverType)] ?? [];

                if (isset($server["gameTypes"])) {
                    $gameTypes = $server["gameTypes"];

                    foreach ($gameTypes as $gameType) {
                        $addCluster($serverManager->getCluster($serverType, $gameType));
                    }
                } else {
                    $addCluster($serverManager->getCluster($serverType));
                }
            };

            $tournamentManager = $ess->getTournamentManager();
            if ($tournamentManager->inTournament() && in_array($npcName, $tournamentManager->getServerTypes(), true)) {
                $name = TextFormat::BOLD . TextFormat::RED . $tournamentManager->getPrettyTimeLeft() . " in Tournament" . TextFormat::RESET . TextFormat::EOL . $name;
            }

            if (str_contains($name, "%PLAYER_COUNT%")) {
                if (ServerManager::AC === $npcName) {
                    foreach ([ServerManager::SC/*, ServerManager::SP*/, ServerManager::MS] as $serverType) {
                        $addServerType($serverType);
                    }
                } else {
                    $addServerType($npcName);
                }

                $name = str_replace(
                    search: "%PLAYER_COUNT%",
                    replace: match (true) {
                        $status[Server::OFFLINE] > 0 && ($status[Server::ONLINE] > 0 || $status[Server::FULL] > 0) => TextFormat::GOLD . "PARTLY ONLINE" . TextFormat::EOL . TextFormat::GREEN . "$onlinePlayers PLAYING",
                        $status[Server::OFFLINE] > 0 => TextFormat::RED . "OFFLINE",
                        $onlinePlayers >= $maxPlayers => TextFormat::RED . "FULL" . TextFormat::EOL . TextFormat::GREEN . "$onlinePlayers PLAYING",
                        default => TextFormat::GREEN . "$onlinePlayers PLAYING"
                    },
                    subject: $name
                );
            }

            return $name;
        };
    }

    public static function spawnCustom(
        EntityManager $entityManager,
        string        $npcName,
        array         $values,
        ?callable     $nameCallable = null
    ): void
    {
        $location = NPCUtils::parseLocation($entityManager->getPlugin()->getServer()->getWorldManager(), $values);

        if (isset($values[NPCUtils::ENTITY_ID])) {
            $npc = new EntityNPC(
                $location,
                $values[NPCUtils::NAME],
                $id = $values[NPCUtils::ENTITY_ID],
                null,
                $nameCallable
            );

            $entityManager->addCustomEntity(new CustomActorList(str_replace("ng:", '', $id), $id));

            $npc->setScale($values[NPCUtils::SCALE] ?? 0.75);
        } else {
            $filename = Path::join("skins", "npcs", strtolower(str_replace(TextFormat::EOL, "", $npcName)) . ".png");

            $npc = new HumanNPC(
                $location,
                $values[NPCUtils::NAME],
                $values[NPCUtils::SKIN] ?? new Skin($npcName . "NPC", SkinUtils::getTextureFromString(Utils::getResourceContent($filename))),
                null,
                $nameCallable
            );

            $npc->setScale($values[NPCUtils::SCALE] ?? 1.5);
        }

        $npc->setCallable(NPCUtils::getCallable($values));

        $entityManager->addEntity($npc);
    }

    public static function spawnNPCs(EntityManager $entityManager): void
    {
        $ess = $entityManager->getPlugin();
        $serverType = $ess->getServerManager()->getServerType();

        foreach (NPCUtils::getServerTypeNPCs()[$serverType] ?? [] as $npcName => $values) {
            NPCUtils::spawnCustom(
                $entityManager,
                $npcName,
                $values,
                NPCUtils::getNameCallable($ess, $npcName)
            );
        }

        foreach (self::getNPCs()[$serverType] ?? [] as $npcName => $values) {
            NPCUtils::spawnCustom(
                $entityManager,
                $npcName,
                $values
            );
        }
    }
}
