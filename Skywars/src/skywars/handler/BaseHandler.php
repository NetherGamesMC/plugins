<?php

declare(strict_types=1);

namespace skywars\handler;

use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\types\game\cage\CagesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\utils\Cage;
use NetherGames\NGEssentials\player\store\categories\SWStore;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\World;
use skywars\entities\EnderDragon;
use skywars\Skywars;
use skywars\SWArena;
use skywars\SWTeam;
use skywars\utils\Items;
use skywars\utils\LuckyBlockManager;
use skywars\utils\Utils;
use function array_map;

abstract class BaseHandler
{
    /** @var Skywars */
    protected Skywars $plugin;
    /** @var SWArena */
    protected SWArena $arena;

    /** @var LuckyBlockManager */
    protected LuckyBlockManager $luckyBlockManager;

    /** @var LuckyBlock[] */
    protected array $replaceLuckyBlockQueue = [];

    public function __construct(SWArena $arena)
    {
        $this->plugin = $arena->getPlugin();
        $this->arena = $arena;
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        if ($this->arena->isRunning() || $this->arena->isFinishing()) {
            $player->setHealthTag(false);

            $player->getXpManager()->setCurrentTotalXp(0);
        }
    }

    public function startGame(): void
    {
        $arena = $this->arena;
        foreach ($arena->getAliveTeams() as $aliveTeam) {
            $spawn = $this->plugin->getArenaConfig()->getTeamSpawn($arena, $aliveTeam->getId());

            foreach ($aliveTeam->getPlayers() as $player) {
                /** @var NGPlayer $player */
                $player->setHealthTag();

                $player->getInventory()->setItem(0, Items::getKitSelector());
                $player->teleport($spawn);
            }
        }

        $this->getCageCosmetic()->runSpawnAnimation($arena->getWorld());

        if ($arena->getType() === SWArena::TYPE_LUCKY_BLOCK) {
            $this->luckyBlockManager = new LuckyBlockManager($this->arena);
            $positions = $this->plugin->getArenaConfig()->getLuckyBlockSpawns($arena);

            foreach ($positions as $position) {
                $luckyBlock = $this->luckyBlockManager->createLuckyBlock($position);
                $luckyBlock->spawnToAll();
            }
        }
    }

    private function getCageCosmetic(): CagesCosmetic
    {
        return $this->arena->isSoloGame() ? CosmeticHandler::SOLO_CAGES() : CosmeticHandler::TEAM_CAGES();
    }

    public function setupMapFeatures(World $world): void
    {
        $cosmetic = $this->getCageCosmetic();
        $cosmetic->spawnCages($world, array_map(function (SWTeam $team) use ($cosmetic): Cage {
            return $cosmetic->getCage($team->getAlivePlayers(), $this->plugin->getArenaConfig()->getTeamSpawn($this->arena, $team->getId()));
        }, $this->arena->getAliveTeams()));
    }

    public function gameTick(int $time, int $timePassed): void
    {
        $arena = $this->arena;
        if ($timePassed < 0) {
            if ($timePassed === -1) {
                $arena->broadcastMessage('§eCages open in ' . TextFormat::RED . '1 §esecond!');
            } else {
                $arena->broadcastMessage('§eCages open in ' . TextFormat::RED . abs($timePassed) . ' §eseconds!');
            }

            if ($timePassed === -10) {
                if ($arena->getType() === SWArena::TYPE_INSANE) {
                    $arena->broadcastTitle(TextFormat::YELLOW . 'Skywars', TextFormat::RED . 'Insane mode', 0, 40, 20);
                } elseif ($arena->getType() === SWArena::TYPE_NORMAL) {
                    $arena->broadcastTitle(TextFormat::YELLOW . 'Skywars', TextFormat::GREEN . 'Normal mode', 0, 40, 20);
                } else {
                    $arena->broadcastTitle(TextFormat::YELLOW . 'Skywars', TextFormat::GREEN . 'LuckyBlock mode', 0, 40, 20);
                }
            } elseif ($timePassed === -8 || ($arena->isDuelsGame() && $timePassed === -3)) {
                foreach ($arena->getAlivePlayers() as $player) {
                    $player->setNoClientPredictions(false);
                }
            }

            if (!$arena->isDuelsGame()) {
                $arena->getScoreboard()->setLine($arena->getPlayers(), 10, CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . 'Opens in ' . date('i:s', abs($timePassed)));
            }

            foreach ($arena->getAliveTeams() as $aliveTeam) {
                $spawn = $arena->getPlugin()->getArenaConfig()->getTeamSpawn($arena, $aliveTeam->getId());

                foreach ($aliveTeam->getAlivePlayers() as $player) {
                    if ($player->getLocation()->distance($spawn) > 5) {
                        $player->teleport($spawn);
                    }

                    //$player->sendTip(TextFormat::YELLOW . 'Selected Kit: ' . TextFormat::GREEN . (Kits::getNames($player)[$arena->getPlugin()->getEssentials()->getPlayerData()->getInt($player, PlayerData::KIT)] ?? 'Random Kit'));
                }
            }
        } elseif ($timePassed === 0) {
            $this->getCageCosmetic()->despawnCages($arena->getWorld());

            $plugin = $arena->getPlugin();
            $kitManager = $plugin->getKitManager();
            $type = $arena->getType();

            /** @var SWStore $category */
            $category = $plugin->getEssentials()->getPlayerManager()->getStore()->getCategory(SWStore::ID);

            foreach ($arena->getAlivePlayers() as $player) {
                /** @var NGPlayer $player */
                $player->getInventory()->clearAll();

                if (!$arena->isPrivateGame()) {
                    $kitId = $category->getSelected($player, $type);
                    if (($kit = $kitManager->getKit($type, $kitId)) !== null) {
                        $kitManager->giveKit($player, $kit);
                    }
                } else {
                    $kit = $arena->getGameSettings()->getKit($player->getId());
                    if ($kit !== null) {
                        $kitManager->giveKit($player, $kit);
                    }
                }

                $player->getEffects()->add(new EffectInstance(VanillaEffects::WATER_BREATHING(), 2 * 20, 0, false));

                $arena->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
                $player->setGamemode(GameMode::SURVIVAL);
                $player->setHealthTag();
            }

            if ($arena->isDuelsGame() && !$arena->isSoloGame()) {
                $arena->getScoreboard()->setLine($arena->getPlayers(), $this->arena->isDuelsGame() ? 8 : 10);
            }

            $arena->broadcastMessage('§eCages opened! §cFIGHT!');
            $this->updateScoreboard($time, $timePassed);
        } else {
            if ($timePassed % 60 === 0) {
                if ($timePassed === (10 * 60)) {
                    $arena->broadcastTitle(TextFormat::LIGHT_PURPLE . 'Ender Dragons Spawned!', '', 0, 60, 20);

                    $dragon = new EnderDragon(Location::fromObject($arena->getWorld()->getSpawnLocation()->add(0, 40, 0), $arena->getWorld()));
                    $dragon->spawnToAll();
                }

                $arena->broadcastMessage('§eThe game ends in §c' . (($time - $timePassed) / 60) . ' §eminutes!');
            } elseif ($timePassed === 20) {
                $arena->setCanUseCorrupted(true);
            }

            foreach ($arena->getAlivePlayers() as $player) {
                if ($player->getInventory()->contains(Items::getCompass()) && ($nearPlayer = Utils::getNearestPlayer($player, array_diff($arena->getAlivePlayers(), $arena->getTeam($player)->getPlayers()))) !== null) {
                    Utils::sendCompassPosition($player, $nearPlayer->getLocation());
                }
            }

            $this->updateScoreboard($time, $timePassed);
            $arena->getChestManager()->tickChestTimer($timePassed);
            $arena->getBorderManager()?->tickBorder();
        }

        if ($arena->getType() === SWArena::TYPE_LUCKY_BLOCK) {
            if ($timePassed === 0) {
                foreach ($arena->getAlivePlayers() as $player) {
                    $player->getInventory()->addItem(VanillaBlocks::STONE()->asItem()->setCount(64));
                }
            }

            $this->replaceLuckyBlockQueue = array_merge($this->replaceLuckyBlockQueue, $this->luckyBlockManager->purgeLuckyBlocks());

            if ($timePassed % 60 === 0) {
                foreach ($this->replaceLuckyBlockQueue as $index => $oldLuckyBlock) {
                    $luckyBlock = $this->luckyBlockManager->createLuckyBlock($oldLuckyBlock->getLocation());
                    $luckyBlock->spawnToAll();

                    unset($this->replaceLuckyBlockQueue[$index]);
                }
            }
        }
    }

    public function updateScoreboard(int $time, int $timePassed): void
    {
        if ($this->arena->getType() === SWArena::TYPE_LUCKY_BLOCK) {
            if ($timePassed < ($time = 600)) {
                $event = 'Dragon spawns';
            } else {
                $event = 'Ends';
            }
        } else if ($timePassed < ($time = 300)) {
            $event = 'Refill';
        } elseif ($timePassed < ($time = 600)) {
            $event = 'Dragon spawns';
        } else {
            $event = 'Ends';
        }

        $this->arena->getScoreboard()->setLine($this->arena->getPlayers(), $this->arena->isDuelsGame() ? 8 : 10, CustomIcon::HOURGLASS . ' ' . TextFormat::GREEN . $event . ' in ' . date('i:s', $time - $timePassed));
    }
}