<?php
/**
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace conquests;

use conquests\shops\menu\ShopMenu;
use conquests\shops\menu\UpgraderMenu;
use conquests\utils\CQArenaConfig;
use conquests\utils\entity\flag\FlagFactory;
use conquests\utils\entity\ItemEntity;
use libminigames\Arena;
use libminigames\Minigame;
use libminigames\utils\Autoloader;
use libminigames\utils\LeaderboardData;
use libVanilla\VanillaPlugin;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function array_merge;
use function array_rand;
use function count;

Autoloader::initAutoloader(dirname(__FILE__, 3) . '/vendor/autoload.php');

class Conquests extends Minigame
{
    /** @var CQArenaConfig */
    private CQArenaConfig $arenaConfig;
    /** @var FlagFactory */
    private FlagFactory $flagFactory;
    /** @var LeaderboardData */
    private LeaderboardData $leaderboards;

    public function getArenaConfig(): CQArenaConfig
    {
        return $this->arenaConfig;
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new CQArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    public function registerClasses(): void
    {
        $arenaConfig = new Config($this->getDataFolder() . 'arenas.yml', Config::YAML, ['arenas' => []]);
        $arenaConfig->save();
        $this->arenaConfig = new CQArenaConfig($arenaConfig);

        $this->leaderboards = new LeaderboardData($this->getModes());

        $this->getServer()->getCommandMap()->register(CQCommand::class, new CQCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new CQListener($this), $this);

        $entityFactory = EntityFactory::getInstance();
        $entityFactory->register(ItemEntity::class, function (World $world, CompoundTag $nbt): ItemEntity {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            if ($itemTag === null) {
                throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            }

            $item = Item::nbtDeserialize($itemTag);
            if ($item->isNull()) {
                throw new SavedDataLoadingException("Item is invalid");
            }
            return new ItemEntity(Helper::parseLocation($nbt, $world), $item, $nbt);
        }, ['Item', 'minecraft:item']);

        $this->flagFactory = new FlagFactory($entityFactory);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $registry = InvMenuHandler::getTypeRegistry();
        foreach ([ShopMenu::MENU_IDENTIFIER, UpgraderMenu::MENU_IDENTIFIER] as $identifier) {
            $registry->register(
                identifier: $identifier,
                type: InvMenuTypeBuilders::DOUBLE_PAIRABLE_BLOCK_ACTOR_FIXED()
                    ->setBlock(VanillaBlocks::CHEST())
                    ->setSize(54)
                    ->setBlockActorId("Chest")
                    ->setAnimationDuration(1)
                    ->build()
            );
        }

        VanillaPlugin::FIREBALL()->register($this);

        $leaderboards = $this->getLeaderboards();
        foreach ($this->getModes() as $i => $mode) {
            $leaderboards->load('cq_wins', $i, -1, '§l§aWINS LEADERBOARD', '§7Most Conquests wins');
            $leaderboards->load('cq_flags_captured', $i, -1, '§l§aFLAGS CAPTURED LEADERBOARD', '§7Most flags captured in Conquests');
            $leaderboards->load('cq_kills', $i, -1, '§l§aKILLS LEADERBOARD', '§7Most kills in Conquests');
        }
    }

    public function getFlagFactory(): FlagFactory
    {
        return $this->flagFactory;
    }

    public function getLeaderboards(): LeaderboardData
    {
        return $this->leaderboards;
    }

    public function getModes(): array
    {
        return [
            CQArena::MODE_MEGA => 'Mega'
        ];
    }

    public function balanceQueuing(): bool
    {
        return true;
    }

    /**
     * @param World $world
     *
     * @return CQArena|null
     */
    public function getArenaByWorld(World $world): ?Arena
    {
        /** @var CQArena $arena */
        $arena = parent::getArenaByWorld($world);

        return $arena;
    }

    public function getRandomBreakMessage(): string
    {
        $messages = [
            '{TEAM} §7was destroyed by {DAMAGER}§r§7!',
            '{TEAM} §7was incinerated by {DAMAGER}§r§7!',
            '{TEAM} §7was iced by {DAMAGER}§r§7!',
            '{TEAM} §7was dismantled by {DAMAGER}§r§7!',
            '{TEAM} §7was deep fried by {DAMAGER}§r§7!',
            '{TEAM} §7was traded in for milk and cookies by {DAMAGER}§r§7!',
            '{TEAM} §7was ripped apart by {DAMAGER}§r§7!',
            '{TEAM} §7be shot with a cannon by {DAMAGER}§r§7!',
            '{TEAM} §7was spooked by {DAMAGER}§r§7!',
            '{TEAM} §7was dreadfully corrupted by {DAMAGER}§r§7!',
            '{TEAM} §7got memed by {DAMAGER}§r§7!',
            '{TEAM} §7was sent to heaven by {DAMAGER}§r§7!',
            '{TEAM} §7got isekaid by {DAMAGER}§r§7!',
            '{TEAM} §7was avada kedavra\'d by {DAMAGER}§r§7!',
            '{TEAM} §7got destoyed by {DAMAGER}§7\'s lightsaber§r§7!',
            '{TEAM} §7was in written in the death note by {DAMAGER}§r§7!',
            '{TEAM} §7was annihilated by {DAMAGER}§r§7!',
            '{TEAM} §7perished because of {DAMAGER}§r§7!',
            '{TEAM} §7was not cozy enough for {DAMAGER}§r§7!',
            '{TEAM} §7will need to sleep with their parents because of {DAMAGER}§r§7!'
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * @return array<int, string>
     */
    public function getMaps(bool $onlyEnabled): array
    {
        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: "/^([a-zA-Z]-)?CQ-([a-zA-Z0-9]+)/",
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }

    public function generateDrop(Player $player, ?Player $damager = null, bool $quit = false): void
    {
        $iron = 0;
        $gold = 0;
        $diamond = 0;
        $emerald = 0;

        foreach ($player->getInventory()->getContents() as $item) {
            match ($item->getTypeId()) {
                ItemTypeIds::IRON_INGOT => $iron += $item->getCount(),
                ItemTypeIds::GOLD_INGOT => $gold += $item->getCount(),
                ItemTypeIds::DIAMOND => $diamond += $item->getCount(),
                ItemTypeIds::EMERALD => $emerald += $item->getCount(),
                default => null
            };
        }

        $items = [
            TextFormat::WHITE => VanillaItems::IRON_INGOT()->setCount($iron),
            TextFormat::GOLD => VanillaItems::GOLD_INGOT()->setCount($gold),
            TextFormat::AQUA => VanillaItems::DIAMOND()->setCount($diamond),
            TextFormat::GREEN => VanillaItems::EMERALD()->setCount($emerald),
        ];

        foreach ($items as $color => $item) {
            if ($item->getCount() === 0) {
                continue;
            }

            if ($quit) {
                $player->dropItem($item);
            } else {
                if ($damager !== null && !$damager->isSpectator()) {
                    $leftOvers = $damager->getInventory()->addItem($item);
                    $damager->sendMessage($color . '+' . $item->getCount() . ' ' . $item->getName() . ($item->getCount() > 1 ? 's' : ''));

                    if (count($leftOvers) > 0) {
                        foreach ($leftOvers as $leftOver) {
                            $player->dropItem($leftOver);
                        }
                    }
                } else {
                    $player->dropItem($item);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getQueuingArenas(int $modeId, string $queuingMode = self::QUEUING_GLOBAL): array
    {
        $queuingArenas = [];
        $queuingMobileArenas = [];

        foreach ($this->getArenas($modeId) as $arena) {
            if (!$arena->isFull() && !$arena->isFinishing()) {
                if ($arena->isTouchOnly()) {
                    if ($queuingMode === self::QUEUING_PREFER_MOBILE) {
                        $queuingMobileArenas[] = $arena;
                    } elseif ($queuingMode === self::QUEUING_FORCE_MOBILE) {
                        $queuingArenas[] = $arena;
                    }
                } elseif ($queuingMode !== self::QUEUING_FORCE_MOBILE) {
                    $queuingArenas[] = $arena;
                }
            }
        }

        return array_merge($queuingMobileArenas, $queuingArenas);
    }

    public function getMinigameTag(): string
    {
        return ServerManager::CQ;
    }
}