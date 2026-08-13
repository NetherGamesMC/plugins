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

namespace NetherGames\NGEssentials;

use factions\player\MMOPlayer;
use libproxy\ProxyNetworkInterface;
use NetherGames\NGEssentials\block\CustomBlockRegistry;
use NetherGames\NGEssentials\entity\custom\EntityNPC;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\events\NGLoginEvent;
use NetherGames\NGEssentials\events\NGPlayerAFKEvent;
use NetherGames\NGEssentials\events\NGRestartEvent;
use NetherGames\NGEssentials\events\PlayerInputChangeEvent;
use NetherGames\NGEssentials\item\CustomItemRegistry;
use NetherGames\NGEssentials\player\forms\Forms;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerStats;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\utils\BaseClass;
use NetherGames\NGEssentials\utils\LobbyItems;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use NetherGames\NGEssentials\utils\skins\SkinValidatorAdapter;
use NetherGames\NGEssentials\utils\SkinUtils;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\EnderChest;
use pocketmine\block\tile\Tile;
use pocketmine\block\Trapdoor;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\command\utils\CommandStringHelper;
use pocketmine\entity\Attribute;
use pocketmine\entity\object\FireworkRocket;
use pocketmine\entity\object\Painting;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEmoteEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\event\player\SessionDisconnectEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\item\PaintingItem;
use pocketmine\item\SpawnEgg;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use RuntimeException;
use function array_key_first;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function str_contains;
use function strtolower;
use const nethergames\COMPOSER_AUTOLOADER_PATH;
use const PHP_INT_MAX;

class EventListener extends BaseClass implements Listener
{

    /**
     * @param PlayerEmoteEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerEmote(PlayerEmoteEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player->hasPermission(Permissions::RANK_EMERALD)) {
            $event->cancel();
        }
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        foreach ($event->getPackets() as $packet) {
            if ($packet instanceof StartGamePacket || $packet instanceof BiomeDefinitionListPacket || $packet instanceof ItemRegistryPacket) {
                if (count($targets = $event->getTargets()) > 1) {
                    throw new RuntimeException("StartGamePacket can only be sent to one player at a time");
                }

                $target = $targets[array_key_first($targets)];
                $protocolId = $target->getProtocolId();

                if ($packet instanceof StartGamePacket) {
                    $packet->levelSettings->experiments = CustomItemRegistry::getExperiments();
                    CustomBlockRegistry::modifyStartGamePacket($packet);

                    if ($protocolId < ProtocolInfo::PROTOCOL_1_21_60) {
                        $packet->itemTable = array_merge($packet->itemTable, CustomItemRegistry::getItemTypeEntries($protocolId));
                    }
                } else if ($packet instanceof BiomeDefinitionListPacket && $target->getProtocolId() < ProtocolInfo::PROTOCOL_1_21_60) {
                    $target->sendDataPacket(CustomItemRegistry::getItemComponentPacket($target->getProtocolId()));
                } else if ($packet instanceof ItemRegistryPacket) {
                    $newEntries = CustomItemRegistry::getItemTypeEntries($protocolId);

                    (function () use ($newEntries): void {
                        /** @var ItemRegistryPacket $this */
                        /** @noinspection PhpUndefinedFieldInspection */
                        $this->entries = array_merge($this->entries, $newEntries);
                    })->call($packet);
                }
            } else if ($packet instanceof ResourcePackStackPacket) {
                $packet->experiments = CustomItemRegistry::getExperiments();
            }
        }
    }

    /**
     * @param PlayerMissSwingEvent $event
     *
     * @priority LOW
     */
    public function onPlayerMissSwing(PlayerMissSwingEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player->isSilent() || $player->isAdventure()) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerInputChangeEvent $event
     *
     * @priority LOW
     */
    public function onPlayerInputChange(PlayerInputChangeEvent $event): void
    {
        if ($event->getNewInputMode() !== InputMode::TOUCHSCREEN) {
            $this->getPlugin()->getPlayerData()->setValue($event->getPlayer(), PlayerData::ALLOW_TOUCH_QUEUEING, false);
        }
    }

    /**
     * @handleCancelled
     */
    public function onDataPacketDecode(DataPacketDecodeEvent $event): void
    {
        if ($event->getPacketId() === NetworkStackLatencyPacket::NETWORK_ID) {
            $event->uncancel();
        }
    }

    /**
     * @param DataPacketReceiveEvent $event
     *
     * @priority LOW
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $origin = $event->getOrigin();
        /** @var NGPlayer $player */
        $player = $origin->getPlayer();
        $packet = $event->getPacket();

        switch ($packet->pid()) {
            case PlayerAuthInputPacket::NETWORK_ID:
                /** @var PlayerAuthInputPacket $packet */
                if ($packet->getInputFlags()->get(PlayerAuthInputFlags::START_FLYING) && !$player->getAllowFlight() && !$player->isSpectator()) {
                    $player->getNetworkSession()->syncAbilities($player);
                }
                $player->handlePlayerAuthInput($packet);
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                if ($packet->trData instanceof UseItemOnEntityTransactionData) {
                    $action = $packet->trData->getActionType();

                    if (!$event->isCancelled() && !$player->isSpectator()) {
                        $entityId = $packet->trData->getActorRuntimeId();
                        $npc = $this->getPlugin()->getEntityManager()->getEntity($player->getWorld(), $entityId);

                        if ($npc instanceof HumanNPC || $npc instanceof EntityNPC) {
                            $npc->onHit($player, $action === UseItemOnEntityTransactionData::ACTION_ATTACK);
                            $event->cancel();
                        }
                    }
                }

                $player->handleInventoryTransaction($packet);
                break;
            case NetworkStackLatencyPacket::NETWORK_ID:
                /** @var NetworkStackLatencyPacket $packet */
                if (isset(\libforms\EventListener::$timestampData[$player->getId()]) && \libforms\EventListener::$timestampData[$player->getId()] === $packet->timestamp) {
                    $attribute = $player->getAttributeMap()->get(Attribute::EXPERIENCE_LEVEL);

                    if ($attribute !== null) {
                        $player->getNetworkSession()->sendDataPacket(UpdateAttributesPacket::create($player->getId(), array_map(static function (Attribute $attr): UpdateAttribute {
                            return new UpdateAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), $attr->getMinValue(), $attr->getMaxValue(), []);
                        }, [$attribute]), $this->getPlugin()->getServer()->getTick()));
                    }

                    $event->cancel();
                    unset(\libforms\EventListener::$timestampData[$player->getId()]);
                }
                break;
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                if (!in_array($packet->protocol, ProtocolInfo::ACCEPTED_PROTOCOL, true)) {
                    if ($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL) {
                        $origin->disconnect('§o§l§eN§6G§r§7: §cPlease update your client to the latest version to play §eNether§6Games§c.');
                    } else {
                        $origin->disconnect("§o§l§eN§6G§r§7: §eNether§6Games §chasn't updated to the latest version yet. Follow us on Twitter for updates - §6@NetherGamesMC");
                    }

                    $event->cancel();
                }
                break;
            case LevelSoundEventPacket::NETWORK_ID:
                /** @var LevelSoundEventPacket $packet */
                $player->handleLevelSound($packet);
                break;
        }
    }

    /**
     * @param NetworkInterfaceRegisterEvent $event
     *
     * @priority LOW
     */
    public function onNetworkInterfaceRegister(NetworkInterfaceRegisterEvent $event): void
    {
        $interface = $event->getInterface();
        if ($interface instanceof RakLibInterface) {
            $plugin = $this->getPlugin();
            if (NGEssentials::isProxyEnabled()) {
                $event->cancel();

                $server = $plugin->getServer();
                $server->getNetwork()->registerInterface(new ProxyNetworkInterface($plugin, $server->getPort(), COMPOSER_AUTOLOADER_PATH));
                $plugin->getLogger()->info('§aProxy interface registered!');
            } else {
                $interface->setPacketLimit(PHP_INT_MAX);
                $plugin->getLogger()->warning('§cProxy interface is not registered.');
            }
        } else if ($interface instanceof DedicatedQueryNetworkInterface && NGEssentials::isProxyEnabled()) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     *
     * @priority LOW
     */
    public function onPlayerExhaust(PlayerExhaustEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player instanceof NGPlayer && $player->isEnergized()) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority LOW
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void
    {
        if (MySQLCredentials::isDatabaseOnline()) {
            $event->clearKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL);
        } elseif ($event->isKickFlagSet(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL)) {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, "§o§l§eN§6G§r§7: §cLooks like §eNether§6Games §cis full, try again later!\nBuy the §l§aEMERALD§r §cor §l§bLEGEND§r §crank at §bngmc.co/store §cto join servers even if they're full!");
            return;
        }

        if ($event->isKickFlagSet(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED)) {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, '§o§l§eN§6G§r§7: §cMaintenance in progress. Follow us on Twitter for updates - §6@NetherGamesMC');
            return;
        }

        $playerInfo = $event->getPlayerInfo();
        $clientData = $playerInfo->getExtraData();
        $playerData = $this->getPlugin()->getPlayerData();

        $this->getPlugin()->getLogger()->info("Waiting for WaterdogPE data layer: {$playerInfo->getUsername()}");

        $playerData->setValue($playerInfo->getUsername(), PlayerData::OFFICIAL_ADDRESS, str_contains($clientData["ServerAddress"], 'nethergames.org') || str_contains($clientData["ServerAddress"], 'ngmc.co'));
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority LOWEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        $serverManager = $this->getPlugin()->getServerManager();

        if ($entity instanceof NGPlayer) {
            if ($entity->isSpectator()) {
                $event->cancel();
                return;
            }

            if ($event instanceof EntityDamageByChildEntityEvent) {
                $event->setKnockBack($event->getKnockBack() * 0.7);
                $event->setAttackCooldown(0);
            } elseif ($event instanceof EntityDamageByEntityEvent) {
                if ($event->getDamager() instanceof FireworkRocket) {
                    $event->cancel();
                    return;
                }

                $event->setKnockBack(0.3875);
                $event->setAttackCooldown(9);
            } elseif ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) !== 0.0 && in_array($event->getCause(), [EntityDamageEvent::CAUSE_CONTACT, EntityDamageEvent::CAUSE_SUFFOCATION, EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_LAVA], true)) {
                $event->cancel();
            }
        }

        if ($serverManager->enableLobbyFeatures($entity->getWorld()) && ($entity instanceof Painting || $entity instanceof NGPlayer)) {
            $event->cancel();
        }
    }

    /**
     * @param EntityTeleportEvent $event
     *
     * @priority LOW
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $player = $event->getEntity();
        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();

        if (!$player instanceof NGPlayer) {
            return;
        }

        $from = $event->getFrom()->getWorld();
        $to = $event->getTo()->getWorld();
        if ($from !== $to) {
            if (!$serverManager->enableLobbyHandling()) {
                return;
            }

            $enableTo = $serverManager->enableLobbyFeatures($to);
            $enableFrom = $serverManager->enableLobbyFeatures($from);

            if ($enableTo && $enableFrom) {
                return;
            }

            $playerData = $plugin->getPlayerData();
            $playerManager = $plugin->getPlayerManager();
            $tracking = $playerData->getBool($player, PlayerData::TRACK);
            if ($enableTo) {
                if (!$tracking) {
                    $player->setGamemode(GameMode::ADVENTURE);
                    $player->setEnergized();

                    if ($serverManager->getServerType() !== ServerManager::CREATIVE) {
                        $playerManager->setStatsBar($player);
                    }

                    if ($player->hasPermission(Permissions::RANK_ULTRA) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK)) {
                        $player->setAllowFlight(true);
                    }
                    LobbyItems::setLobbyInventory($player);
                }

                $playerManager->sendLobbyScoreBoard($player);
            } elseif ($enableFrom) {
                if (!$tracking) {
                    if ($serverManager->getServerType() === ServerManager::CREATIVE) {
                        $player->setGamemode(GameMode::CREATIVE);
                    } else {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                        $player->getXpManager()->setCurrentTotalXp(0);
                    }
                    /** @phpstan-ignore-next-line */
                    if ($serverManager->getServerType() !== ServerManager::FACTIONS || ($player instanceof MMOPlayer && $player->getDuelsArena() === null)) {
                        $player->getInventory()->clearAll();
                        $player->setEnergized(false);
                    }
                }

                $plugin->getServerData()->getScoreBoard()->removePlayer($player);
            }

            $playerManager->updatePlayerVisibility($player, $to);
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority LOWEST
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $player = $event->getTransaction()->getSource();
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($player->getWorld())) {
            $event->cancel();
        }

        if ($player->isSpectator() && !$player->hasPermission(Permissions::RANK_OWNER)) {
            $event->cancel();
        }

        if ($event->isCancelled()) {
            $cursorInventory = $player->getCursorInventory();
            $cursorInventory->setItem(0, $cursorInventory->getItem(0));
        }
    }

    /**
     * @param PlayerItemHeldEvent $event
     *
     * @priority LOW
     */
    public function onPlayerHeldItem(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Utils::hasClassicUI($player) && $this->getPlugin()->getServerManager()->enableLobbyFeatures($player->getWorld())) {
            $this->onItemInteract($player, $event->getItem());
        }
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        if ($item->equals(LobbyItems::getTeleporterItem())) {
            Forms::sendMinigameSelector($player, $this->getPlugin());
        } elseif ($item->equals(LobbyItems::getCosmeticItem())) {
            $this->getPlugin()->getPlayerManager()->getCosmeticHandler()->sendForm($player);
        } elseif ($item->equals(LobbyItems::getProfileSettingsItem())) {
            Forms::sendSettings($player, $this->getPlugin());
        } else {
            return false;
        }

        return true;
    }

    /**
     * @param BlockMeltEvent $event
     *
     * @priority NORMAL
     */
    public function onBlockMelt(BlockMeltEvent $event): void
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($event->getBlock()->getPosition()->getWorld()) || $serverManager->getServerType() === ServerManager::SETUP) {
            $event->cancel();
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @priority LOWEST
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        $serverManager = $this->getPlugin()->getServerManager();
        if (!$player->hasPermission(Permissions::RANK_OWNER) && $serverManager->enableLobbyFeatures($player->getWorld())) {
            $event->cancel();
        }
    }

    /**
     * @param BlockBreakEvent $event
     * @priority LOWEST
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();

        $serverManager = $this->getPlugin()->getServerManager();
        if (!$player->hasPermission(Permissions::RANK_OWNER) && $serverManager->enableLobbyFeatures($player->getWorld())) {
            $event->cancel();
        }
    }

    /**
     * @param EntityItemPickupEvent $event
     *
     * @priority NORMAL
     */
    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        if ($this->getPlugin()->getServerManager()->enableLobbyFeatures($event->getEntity()->getWorld())) {
            $event->cancel();
        }
    }

    /**
     * @param BlockSpreadEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($event->getBlock()->getPosition()->getWorld()) || $serverManager->getServerType() === ServerManager::SETUP) {
            $event->cancel();
        }
    }

    /**
     * @param LeavesDecayEvent $event
     *
     * @priority LOWEST
     */
    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($event->getBlock()->getPosition()->getWorld()) || $serverManager->getServerType() === ServerManager::SETUP) {
            $event->cancel();
        }
    }

    /**
     * @param BlockGrowEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($event->getBlock()->getPosition()->getWorld()) || $serverManager->getServerType() === ServerManager::SETUP) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     *
     * @priority LOW
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($player->getWorld()) && $this->onItemInteract($player, $event->getItem())) {
            $event->cancel();
        }
    }

    /**
     * @param WorldLoadEvent $event
     *
     * @priority LOWEST
     */
    public function onWorldLoad(WorldLoadEvent $event): void
    {
        $world = $event->getWorld();
        $serverManager = $this->getPlugin()->getServerManager();

        $world->setDifficulty(World::DIFFICULTY_HARD);

        if ($serverManager->isMMOGame()) {
            $world->setAutoSave(true);
        } else {
            $world->setAutoSave(!$serverManager->enableLobbyFeatures($world));

            $world->setTime(World::TIME_DAY);
            $world->stopTime();
        }
    }

    /**
     * @param PlayerChangeSkinEvent $event
     *
     * @priority LOW
     */
    public function onPlayerChangeSkin(PlayerChangeSkinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $skin = $event->getNewSkin();
        $player->setOriginalSkin($skin);

        if ($skin->getSkinId() === SkinValidatorAdapter::INVALID_SKIN) {
            $player->sendMessage(TextFormat::RED . 'Your skin was changed because it was invalid.');
        }
        SkinUtils::saveSkin($player, $skin);
    }

    /**
     * @param NGLoginEvent $event
     *
     * @priority LOWEST
     */
    public function onNGLogin(NGLoginEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();

        if ($event->isPreLoaded()) {
            if ($this->getPlugin()->getServerManager()->getServerType() !== ServerManager::FACTIONS) {
                $player->toggleGameRule('showcoordinates', false);
            }
        } else {
            $player->setLocatorBarEnabled(false);
        }
    }

    /**
     * @param NGRestartEvent $event
     *
     * @priority LOWEST
     */
    public function onRestart(NGRestartEvent $event): void
    {
        foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
            $this->getPlugin()->getPlayerData()->saveData($player);
        }
    }

    /**
     * @param PlayerCreationEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerCreation(PlayerCreationEvent $event): void
    {
        $plugin = $this->getPlugin();

        $event->setBaseClass(NGPlayer::class);
        if ($plugin->getServerManager()->getServerType() !== ServerManager::SB) {
            $event->setPlayerClass(NGPlayer::class);
        }

        $networkSession = $event->getNetworkSession();
        $resolver = new PromiseResolver();
        $event->addPromise($resolver->getPromise());

        $callback = function (bool $success) use ($resolver): void {
            $resolver->resolve(null);
        };

        $plugin->getPlayerData()->loadPlayerData($networkSession, $callback);
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * @priority NORMAL
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void
    {
        $event->setDeathMessage('');
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority LOW
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $plugin = $this->getPlugin();
        $serverManager = $plugin->getServerManager();

        if ($player->hasPermission(Permissions::RANK_OWNER)) {
            if (NGEssentials::isInDevelopmentMode() && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                $player->sendMessage((string)$event->getBlock()->getPosition());

            }
        } else if ($serverManager->enableLobbyFeatures($player->getWorld())) {
            $event->cancel();
            return;
        }

        if ($player->isAdventure() && ($block instanceof Trapdoor || $block instanceof Door)) {
            $event->cancel();
            return;
        }

        if ($block instanceof Chest || $block instanceof EnderChest) {
            $pos = $block->getPosition();
            $tile = $pos->getWorld()->getTile($pos);

            if ($tile === null) {
                /** @var string $class */
                $class = $block->getIdInfo()->getTileClass();

                /**
                 * @var Tile $tile
                 * @see Tile::__construct()
                 */
                $tile = new $class($pos->getWorld(), $pos->asVector3());
                $pos->getWorld()->addTile($tile);
            }
        }

        if ($player->isSneaking()) {
            $item = $event->getItem();
            $event->setUseItem($item instanceof Bucket || $item instanceof SpawnEgg || $item instanceof PaintingItem);
        }
    }

    /**
     * @param NGPlayerAFKEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerAFK(NGPlayerAFKEvent $event): void
    {
        $player = $event->getPlayer();
        $serverManager = $this->getPlugin()->getServerManager();

        if (Permissions::isStaff($player)) {
            return;
        }

        if ($serverManager->isMinigame()) {
            $player->sendMessage('§cYou have been transferred to the lobby for being inactive in a game.');
            $this->getPlugin()->getPlayerManager()->transferPlayer($player);
        } elseif (!$player->hasPermission(Permissions::RANK_EMERALD) && $serverManager->getServerType() !== ServerManager::LOBBY) {
            $player->sendMessage('§cYou have been transferred to the lobby for being inactive for a while. Buy the §l§aEMERALD §r§cor §l§bLEGEND §r§crank at §bngmc.co/store §cto remove this restriction!');
            $this->getPlugin()->getPlayerManager()->transferPlayer($player);
        }
    }

    /**
     * @param PlayerBlockPickEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerBlockPick(PlayerBlockPickEvent $event): void
    {
        if ($event->getPlayer()->isSpectator()) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerToggleSwimEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerToggleSwim(PlayerToggleSwimEvent $event): void
    {
        $event->cancel();
    }

    /**
     * @param PlayerLoginEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();

        $playerData = $this->getPlugin()->getPlayerData();
        $production = !NGEssentials::isInDevelopmentMode();
        $serverManager = $this->getPlugin()->getServerManager();

        $player->setNGLanguage(Translator::translateLocale($player->getLocale()));

        $preloaded = $playerData->getBool($player, PlayerData::PRELOADED);
        $playerManager = $this->getPlugin()->getPlayerManager();

        if ($production && !$preloaded && $serverManager->getServerType() !== ServerManager::LOBBY) {
            $playerManager->transferPlayer($player);
            return;
        }

        $playerManager->setupPlayer($player);
    }

    /**
     * @param CommandEvent $event
     *
     * @priority LOWEST
     */
    public function onCommand(CommandEvent $event): void
    {
        $playerData = $this->getPlugin()->getPlayerData();
        $sender = $event->getSender();

        if ($sender instanceof NGPlayer) {
            if (!$playerData->getBool($sender, PlayerData::SETUP)) {
                $sender->sendMessage($this->getPlugin()->getPrefix() . '§cYou cannot use this command while in setup mode.');
                $event->cancel();
            }
        }

        $args = CommandStringHelper::parseQuoteAware($event->getCommand());
        $commandName = array_shift($args);
        if ($commandName === null) {
            return;
        }

        $command = $this->getPlugin()->getServer()->getCommandMap()->getCommand(strtolower($commandName));
        if ($command !== null) {
            $event->setCommand(implode(" ", [$command->getName(), ...array_map(static function (string $arg): string {
                return str_contains($arg, ' ') ? "\"$arg\"" : $arg;
            }, $args)]));

            if ($sender instanceof NGPlayer && $command instanceof GamemodeCommand && !$command->testPermissionSilent($sender)) {
                $playerManager = $this->getPlugin()->getPlayerManager();
                $arena = $playerManager->isInArena($sender, true);
                $partyManager = $playerManager->getSocialManager()->getPartyManager();

                if ($arena !== false && $arena->isPrivateGame() && ($partyManager->isPartyCreator($sender) || Permissions::isStaff($sender))) {
                    $permissionAttachment = $sender->getPermissionAttachment();
                    $permissionAttachment->setPermission(DefaultPermissionNames::COMMAND_GAMEMODE_SELF, true);
                    $command->execute($sender, 'gamemode', $args);
                    $permissionAttachment->unsetPermission(DefaultPermissionNames::COMMAND_GAMEMODE_SELF);

                    $event->cancel();
                }
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $playerData = $this->getPlugin()->getPlayerData();
        $setup = $playerData->getBool($player, PlayerData::SETUP);
        $preloaded = $playerData->getBool($player, PlayerData::PRELOADED);

        if ($setup) {
            $ev = new NGJoinEvent($player, $preloaded);
            $ev->call();
        }

        if (!$playerData->getBool($player, PlayerData::GLOBAL_CHAT) && ($this->getPlugin()->getServerManager()->getServerType() === ServerManager::LOBBY || mt_rand(1, 10) === 10)) {
            $player->sendMessage("§c§lWARNING§r§c: You have disabled global chat!");
            $player->sendMessage(TextFormat::YELLOW . "Run /cfx to turn on global chat.");
        }

        $event->setJoinMessage('');
    }

    /**
     * @param NGJoinEvent $event
     *
     * @priority MONITOR
     */
    public function onNGJoinFinished(NGJoinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $player->setLoaded();
        $player->spawnToAll();
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $serverManager = $this->getPlugin()->getServerManager();

        if (($otherCluster = $serverManager->getQueuedCluster($player)) !== null) {
            $otherCluster->removeFromQueue($player);
        }

        PlayerStats::saveOnlineTime($player);

        $this->getPlugin()->getPlayerData()->saveData($player, true);
        $event->setQuitMessage('');
    }

    public function onSessionDisconnect(SessionDisconnectEvent $event): void
    {
        $session = $event->getSession();

        if (($playerInfo = $session->getPlayerInfo()) !== null) {
            $this->getPlugin()->getPlayerData()->unsetValue($playerInfo->getUsername());
        }

        if (ServerManager::$waitingForDrain && count($this->getPlugin()->getServer()->getOnlinePlayers()) === 0) {
            $this->getPlugin()->getServer()->shutdown();
        }
    }

    /**
     * @param CraftItemEvent $event
     *
     * @priority LOW
     */
    public function onCraftItem(CraftItemEvent $event): void
    {
        if ($this->getPlugin()->getServerManager()->enableLobbyFeatures($event->getPlayer()->getWorld())) {
            $event->cancel();
        }
    }

    /**
     * @param BlockUpdateEvent $event
     *
     * @priority LOWEST
     */
    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $serverManager = $this->getPlugin()->getServerManager();

        if ($serverManager->enableLobbyFeatures($event->getBlock()->getPosition()->getWorld()) || $serverManager->getServerType() === ServerManager::SETUP) {
            $event->cancel();
        }
    }


    /**
     * @param EntityCombustEvent $event
     *
     * @priority LOW
     */
    public function onEntityCombust(EntityCombustEvent $event): void
    {
        if ($this->getPlugin()->getServerManager()->enableLobbyFeatures($event->getEntity()->getWorld())) {
            $event->cancel();
        }
    }

    /**
     * @param QueryRegenerateEvent $event
     *
     * @priority LOWEST
     */
    public function onQueryRegenerate(QueryRegenerateEvent $event): void
    {
        $queryInfo = $event->getQueryInfo();

        $queryInfo->setServerName($this->getPlugin()->getServerManager()->getMotd());
        $queryInfo->setListPlugins(false);
        $queryInfo->setPlayerList([]);
        $queryInfo->setMaxPlayerCount(count($this->getPlugin()->getServer()->getOnlinePlayers()) + 16);
    }

    /**
     * @param PlayerToggleFlightEvent $event
     *
     * @priority LOWEST
     */
    public function onPlayerToggleFlight(PlayerToggleFlightEvent $event): void
    {
        if ($event->isFlying() && !$event->getPlayer()->getAllowFlight() && !$event->getPlayer()->isSpectator()) {
            $event->cancel();
        }
    }
}
