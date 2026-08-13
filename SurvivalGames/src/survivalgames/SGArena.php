<?php

declare(strict_types=1);

namespace survivalgames;

use libminigames\tasks\CountDownTask;
use libminigames\utils\BlockCollector;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use survivalgames\chest\ChestManager;
use survivalgames\task\MatchTimeTask;
use survivalgames\task\PlayersTickTask;
use survivalgames\utils\border\CircleManager;
use survivalgames\utils\cage\CageManager;
use survivalgames\utils\Items;
use survivalgames\utils\StatsData;
use function array_keys;
use function array_map;
use function array_rand;
use function count;
use function min;

class SGArena extends SGTypeArena
{

    // Use bitwise operators for this method, more simple and less maintenance required.
    public const DEATHMATCH_NOT_READY = 0x1;
    public const DEATHMATCH_VOTES_WAITING = 0x2;
    public const DEATHMATCH_RUNNING = 0x3;
    public const ALLOW_KILLSTREAK = 0x4;
    public const PLAYERS_INVINCIBLE = 0x5;

    public const ACIDIC_WATER = 0x6;
    public const IS_RAINING = 0x7;
    public const IS_CHAOTIC = 0x8;
    public const IS_MOB_ANGER = 0x9;
    public const CAN_RAIN = 0xA; // Drybone valley map cannot have rainfall.

    /** @var SGEventManager */
    private SGEventManager $events;

    /** @var CircleManager */
    private CircleManager $borderManager;
    /** @var CageManager */
    private CageManager $cageManager;
    /** @var ChestManager */
    private ChestManager $chestManager;
    /** @var BlockCollector */
    private BlockCollector $blockCollector;

    /** @var int */
    private int $gameFlags = 0x0;

    public function __construct(SurvivalGames $plugin, int $modeId, int $id, bool $privateGame)
    {
        parent::__construct($plugin, $modeId, $id, $privateGame);

        $this->listener = new SGArenaListener($this);
        $this->events = new SGEventManager($this);
        $this->chestManager = new ChestManager($this);
        $this->blockCollector = new BlockCollector();
        $this->statsData = new StatsData($plugin->getModeName($modeId), SGArena::getTypes());

        $arenas = $plugin->getMaps(!$privateGame);
        $maps = $privateGame ? array_keys($arenas) : array_rand($arenas, min(5, count($arenas)));

        if (is_array($maps)) {
            foreach ($maps as $map) {
                $this->maps[] = $arenas[$map];
            }
        } else {
            $this->maps[] = $arenas[$maps];
        }
    }

    public function getBlockCollector(): BlockCollector
    {
        return $this->blockCollector;
    }

    public function addKill(Player $player, Player $victim): void
    {
        $this->playKillCosmetics($player);

        if (!$this->isSpectator($player)) {
            $effects = $player->getEffects();
            $effects->add(new EffectInstance(VanillaEffects::REGENERATION(), 20 * 5));
            $effects->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5));
        }

        $statsData = $this->getStatsData();
        $statsData->addKill($player, $victim, StatsData::KILLS);
        $statsData->addKill($player, $victim, StatsData::SG_KILLS);

        $this->getScoreboard()->setLine([$player], 5, CustomIcon::KILLS . TextFormat::GREEN . $statsData->getValue($player, StatsData::SG_KILLS));
    }

    public function hasFlags(int $flagId): bool
    {
        return (($this->gameFlags >> $flagId) & 1) === 1;
    }

    public function sendStats(): void
    {
        $this->getStatsData()->sendLeaderboard($this, StatsData::SG_KILLS, '§l§aTOP KILLERS');
    }

    public function addParticipation(Player $player, array $data, bool $guildXP = false): void
    {
        if (!$this->isPrivateGame()) {
            $guildXP = true;

            $type = $this->getType();

            if ($this->isWinner($player)) {
                $data[self::DATA_CREDITS][] = [
                    'Win',
                    (match ($type) {
                        SGTypeArena::TYPE_NORMAL => 12,
                        SGTypeArena::TYPE_HARDCORE => 16,
                        default => 12
                    })
                ];
            }

            if (($kills = $this->getStatsData()->getValue($player, StatsData::SG_KILLS)) > 0) {
                $data[self::DATA_CREDITS][] = [
                    $kills . ' Kill' . ($kills > 1 ? 's' : ''),
                    $kills * (match ($type) {
                        SGTypeArena::TYPE_NORMAL => 3,
                        SGTypeArena::TYPE_HARDCORE => 4,
                        default => 3
                    })
                ];
            }
        }

        parent::addParticipation($player, $data, $guildXP);
    }

    public function getBorderManager(): CircleManager
    {
        return $this->borderManager;
    }

    public function getEventManager(): SGEventManager
    {
        return $this->events;
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if ($this->isWaiting() && $player->hasPermission('nethergames.voter')) {
            $player->getInventory()->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getTypeSelectionAnvil());
        }
    }

    public function getStreaksKey(): ?string
    {
        if ($this->isPrivateGame()) return null;
        return strtolower($this->getPlugin()->getMinigameTag() . "_" . $this->getPlugin()->getModes()[$this->getModeId()]);
    }

    public function bootMinigame(): void
    {
        $this->cageManager = new CageManager($this);
        $this->setArenaFlag(self::DEATHMATCH_NOT_READY, true);
        $this->setArenaFlag(self::PLAYERS_INVINCIBLE, true);

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new CountDownTask($this), 20);
    }

    public function getMaxSize(): int
    {
        return 24;
    }

    public function setArenaFlag(int $flagId, bool $flags): void
    {
        if ($flags) {
            $this->gameFlags |= 1 << $flagId;
        } else {
            $this->gameFlags &= ~(1 << $flagId);
        }
    }

    public function despawnEntities(Player $player): void
    {
        foreach ($this->getChestManager()->getChestTimers() as $chest) {
            $chest->despawn($player);
        }
    }

    public function getChestManager(): ChestManager
    {
        return $this->chestManager;
    }

    public function resetPlayer(Player $player): void
    {
        /** @var NGPlayer $player */
        parent::resetPlayer($player);

        if ($this->isRunning() || $this->isFinishing()) {
            if ($this->isNormal()) {
                $player->setHealthTag(false);
            }

            $player->getXpManager()->setCurrentTotalXp(0);

            $this->removeDeathmatch($player);
        }
    }

    public function setupMapFeatures(World $world): void
    {
        $this->getCageManager()->spawnCages($world);

        $this->setArenaFlag(self::CAN_RAIN, $this->getMapDisplayName() !== "Drybone Valley");

        $this->borderManager = new CircleManager($this->getMidpoint(), $this->getPlugin()->getArenaConfig()->getBorderSettings($this));
    }

    public function getCageManager(): CageManager
    {
        return $this->cageManager;
    }

    public function getMidpoint(): Vector3
    {
        return $this->getPlugin()->getArenaConfig()->getMidpoint($this);
    }

    public function startGame(): void
    {
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextUtils::center('Survival Games'), true);
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . 'Survive while eliminating'), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . "as many players as you can!"), true);
        $this->broadcastMessage(TextUtils::center(TextFormat::YELLOW . TextFormat::BOLD . "Last survivor wins!"), true);
        if (!$this->isNormal()) {
            $this->broadcastMessage(TextUtils::center(TextFormat::RED . TextFormat::BOLD . "Game UI is limited in hardcore."), true);
        }
        $this->broadcastMessage('', true);
        $this->broadcastMessage(TextFormat::GREEN . TextFormat::BOLD . '----------------------------', true);
        $this->broadcastMessage(TextFormat::RED . TextFormat::BOLD . 'Teaming is not allowed on Solo mode!', true);

        $this->getCageManager()->teleportToCages();

        foreach ($this->getAlivePlayers() as $player) {
            /** @var NGPlayer $player */
            $player->setNoClientPredictions(false);
            $player->setEnergized();
        }

        if ($this->getType() === SGTypeArena::TYPE_HARDCORE) {
            $this->broadcastTitle(TextFormat::YELLOW . 'Survival Games', TextFormat::RED . 'Hardcore mode', 0, 40, 20);
        } else {
            $this->broadcastTitle(TextFormat::YELLOW . 'Survival Games', TextFormat::GREEN . 'Normal mode', 0, 40, 20);
        }

        $array = [
            12 => '',
            11 => CustomIcon::HOURGLASS . TextFormat::GREEN . 'Opens in 0:10',
            10 => '',
            9 => CustomIcon::WOODEN_CHEST . TextFormat::GREEN . 'None',
            8 => '',
            7 => CustomIcon::PLAYERS_TINY . TextFormat::GREEN . count($this->getAlivePlayers()),
            6 => '',
            5 => CustomIcon::KILLS . TextFormat::GREEN . 0,
            4 => '',
            3 => CustomIcon::MAP . TextFormat::GREEN . $this->getMapDisplayName(),
            2 => '',
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
        ];
        $this->getScoreboard()->setLines($this->getPlayers(), $array);

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new MatchTimeTask($this), 20);
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new PlayersTickTask($this), 1);
    }
}
