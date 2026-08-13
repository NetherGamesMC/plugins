<?php
/**
 *     _______ _          ____       _     _
 *    |__   __| |        |  _ \     (_)   | |
 *  __  _| |  | |__   ___| |_) |_ __ _  __| | __ _  ___
 *  \ \/ / |  | '_ \ / _ \  _ <| '__| |/ _` |/ _` |/ _ \
 *   >  <| |  | | | |  __/ |_) | |  | | (_| | (_| |  __/
 *  /_/\_\_|  |_| |_|\___|____/|_|  |_|\__,_|\__, |\___|
 *                                            __/ |
 *                                           |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author Ragnok123
 *
 */
declare(strict_types=1);

namespace bridges;

use bridges\menu\LayoutEditor;
use bridges\utils\Items;
use bridges\utils\KitManager;
use bridges\utils\StatsData;
use bridges\utils\Utils;
use libminigames\Team;
use libminigames\TeamArena;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\utils\Cage;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\TextUtils;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function str_replace;

class BridgeTeam extends Team
{
    /** @var int */
    private int $score = 0;
    /** @var Vector3 */
    private Vector3 $pointPos;
    /** @var Location */
    private Location $spawnPos;
    /** @var Item[] */
    private array $armorContents = [];

    public function getPoint(): Vector3
    {
        return $this->pointPos;
    }

    public function queuePlayer(Player $player): void
    {
        parent::queuePlayer($player);

        if ($this->getArena()->isWaiting()) {
            if ($this->getArena()->isSoloGame()) {
                $player->getInventory()->setItem(Items::EXTRA_SOLO_ITEM_0, Items::getPreferencesSelector());
            } else {
                $player->getInventory()->setItem(Items::EXTRA_ITEM_1, Items::getPreferencesSelector());
            }
        }
    }

    public function setupTeam(World $world): void
    {
        $arenaConfig = $this->getArena()->getPlugin()->getArenaConfig();
        $this->pointPos = $arenaConfig->getTeamPoint($this->getArena(), $this->getId());
        $this->spawnPos = $arenaConfig->getTeamSpawn($this->getArena(), $world, $this->getId());

        $this->getArena()->getPlugin()->getEssentials()->getEntityManager()->addEntity(new FloatingText(Location::fromObject($this->pointPos, $world), TextFormat::BOLD . $this->getColor() . ucfirst($this->getName()) . ' Goal', TextFormat::ITALIC . TextFormat::YELLOW . 'Jump in to score!'));

        $helmet = VanillaItems::LEATHER_CAP();
        $chestplate = VanillaItems::LEATHER_TUNIC();
        $leggings = VanillaItems::LEATHER_PANTS();
        $boots = VanillaItems::LEATHER_BOOTS();

        $rgb = Utils::textFormatToRGB($this->getColor());
        $color = new Color($rgb[0], $rgb[1], $rgb[2]);

        $helmet->setCustomColor($color);
        $chestplate->setCustomColor($color);
        $leggings->setCustomColor($color);
        $boots->setCustomColor($color);

        $helmet->setUnbreakable();
        $chestplate->setUnbreakable();
        $leggings->setUnbreakable();
        $boots->setUnbreakable();

        $protectionLevel = KitManager::getArmorProtectionLevel($this->getArena()->getGameSettings()->getKit());
        $enchantment = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), $protectionLevel);

        if ($protectionLevel > 0) {
            $helmet->addEnchantment($enchantment);
            $chestplate->addEnchantment($enchantment);
            $leggings->addEnchantment($enchantment);
            $boots->addEnchantment($enchantment);
        }

        $boots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING()));

        $this->armorContents = [
            $helmet,
            $chestplate,
            $leggings,
            $boots,
        ];
    }

    /**
     * @return BridgeArena
     */
    public function getArena(): TeamArena
    {
        /** @var BridgeArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function getPointAABB(): AxisAlignedBB
    {
        return new AxisAlignedBB(
            $this->pointPos->x - 1.5,
            $this->pointPos->y - 1,
            $this->pointPos->z - 1.5,
            $this->pointPos->x + 1.5,
            $this->pointPos->y + 1,
            $this->pointPos->z + 1.5
        );
    }

    public function addGoal(Player $player): void
    {
        $arena = $this->getArena();

        $this->addScore();
        $arena->addGoal($player);

        $otherTeam = $arena->getOtherTeam($this);
        $scoreText = $this->getColor() . $this->getScore() . TextFormat::GRAY . ' - ' . $otherTeam->getColor() . $otherTeam->getScore();

        $arena->broadcastTitle($this->getPlayerName($player) . " scored!", $scoreText, 0, 120);
        $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextUtils::center($this->getPlayerName($player) . TextFormat::GRAY . ' (' . TextFormat::GREEN . round($player->getHealth() / 2, 1) . CustomIcon::HEART . TextFormat::GRAY . ') ' . TextFormat::YELLOW . 'scored!'), true);
        $arena->broadcastMessage(TextUtils::center($scoreText), true);
        $arena->broadcastMessage('', true);
        $arena->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . '----------------------------', true);

        foreach ($arena->getTeams() as $team) {
            $arena->getScoreboard()->setLine($team->getPlayers(), 10, $arena->getScoreVisuals($team));
            $arena->getScoreboard()->setLine($team->getPlayers(), 9, $arena->getScoreVisuals($arena->getOtherTeam($team)));
        }

        if ($this->getScore() >= $arena->goalLimit) {
            $arena->phase = BridgeArena::PHASE_FINISH;

            $player->teleport($this->getSpawnPosition());
        } else {
            $arena->phase = BridgeArena::PHASE_RESTART;
            $arena->time = 6;

            $this->getArena()->spawnCages();

            foreach ($arena->getTeams() as $team) {
                $team->respawnAllPlayers();
            }
        }
    }

    public function addScore(): void
    {
        $this->score++;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getSpawnPosition(): Location
    {
        return $this->spawnPos;
    }

    public function respawnAllPlayers(bool $start = false): void
    {
        foreach ($this->getPlayers() as $player) {
            $this->respawnPlayer($player);

            if ($start) {
                /** @var NGPlayer $player */
                $player->setEnergized();
            }

            $player->setGamemode(GameMode::ADVENTURE);
            $player->setNoClientPredictions(true);
        }
    }

    public function respawnPlayer(Player $player): void
    {
        $this->getArena()->resetPlayer($player);

        /** @var NGPlayer $player */
        $player->setHealthTag();

        $kitType = $this->getArena()->getGameSettings()->getKit();
        
        KitManager::applyKitEffects($player, $kitType);
        
        $player->getArmorInventory()->setContents($this->armorContents);
        
        if ($kitType !== BridgeSettings::KIT_NO_KIT) {
            $player->getInventory()->setContents(LayoutEditor::getContents($player));
        }

        $player->teleport($this->getSpawnPosition());
    }

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        parent::removePlayer($player, $teamChange);

        if ($this->getArena()->isRunning() && !$this->getArena()->isSpectator($player)) {
            $ess = $this->getArena()->getPlugin()->getEssentials();

            if (($damager = $ess->getCombatLogger()->getLatestHit($player)) !== null && $this->getArena()->isInArena($damager)) {
                $this->getArena()->broadcastMessage(str_replace(['{PLAYER}', '{DAMAGER}'], [$player->getNameTag(), $damager->getNameTag()], $this->getArena()->getPlugin()->getRandomKillMessage(1)), true);

                $this->getArena()->addKill($damager, $player);
            }

            $statsData = $this->getArena()->getStatsData();
            $statsData->addValue($player, StatsData::TB_DEATHS);
            $statsData->addValue($player, StatsData::TB_MODE_DEATHS);
        }
    }

    public function reconnectPlayer(Player $player): void
    {
        $arena = $this->getArena();
        $this->addPlayer($player);
        $player->setNameTag($this->getPlayerName($player, true));

        $arena->broadcastMessage($this->getPlayerName($player) . TextFormat::GRAY . ' reconnected', true);

        $this->respawnPlayer($player);

        /** @var NGPlayer $player */
        $player->setEnergized();
        $player->setGamemode(GameMode::SURVIVAL);

        $arena->getScoreboard()->addPlayer($player);
        $this->sendScoreboard($player);
    }

    public function sendScoreboard(?Player $player = null): void
    {
        $arena = $this->getArena();
        $statsData = $arena->getStatsData();

        $arena->getScoreboard()->setLines($player === null ? $this->getPlayers() : [$player], [
            13 => '',
            12 => CustomIcon::HOURGLASS,
            11 => '',
            10 => $arena->getScoreVisuals($this),
            9 => $arena->getScoreVisuals($arena->getOtherTeam($this)),
            8 => '',
            7 => CustomIcon::KILLS . TextFormat::GREEN . ($player === null ? 0 : $statsData->getValue($player, StatsData::TB_KILLS)),
            6 => CustomIcon::TARGET . TextFormat::GREEN . ($player === null ? 0 : $statsData->getValue($player, StatsData::TB_GOALS)),
            5 => '',
            4 => CustomIcon::MAP . TextFormat::GREEN . $arena->getMapDisplayName(),
            3 => CustomIcon::GAMEMODE . TextFormat::GREEN . $arena->getPlugin()->getModeName($arena->getModeId()),
            2 => '',
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . 'ngmc.co'
        ]);
    }

    public function releasePlayers(): void
    {
        foreach ($this->getPlayers() as $player) {
            $player->setGamemode(GameMode::SURVIVAL);
        }
    }

    public function generateCage(?World $world): Cage
    {
        $arena = $this->getArena();
        $arenaConfig = $arena->getPlugin()->getArenaConfig();

        return CosmeticHandler::TEAM_CAGES()->getCage(
            $this->getAlivePlayers(),
            $arenaConfig->getTeamSpawn($arena, $world, $this->getId()),
            $this->getId() === self::RED ? 0 : 1
        );
    }
}