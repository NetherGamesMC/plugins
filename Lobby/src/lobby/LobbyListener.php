<?php

declare(strict_types=1);

namespace lobby;

use lobby\entity\minecraft\NPC;
use lobby\forms\Forms;
use lobby\utils\BaseTrait;
use lobby\utils\Items;
use NetherGames\NGEssentials\events\NGJoinEvent;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerData;
use NetherGames\NGEssentials\utils\Utils;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function array_rand;
use function count;

class LobbyListener implements Listener
{
    use BaseTrait;

    /**
     * @param NGJoinEvent $event
     * @priority LOW
     */
    public function onNGJoin(NGJoinEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();

        if (!$event->isPreLoaded()) {
            $player->sendMessage(Translator::getTranslationPlayer($player, "join.welcome", Translator::TYPE_SUCCESS));
            //$player->sendMessage(Translator::getTranslationPlayer($player, "join.vote", Translator::TYPE_INFO));

            $this->sendTitle($player);
        }

        $this->getPlugin()->getPlayerManager()->setupPlayer($player);

        $essPlayerManager = $this->getNGEssentials()->getPlayerManager();
        $essPlayerManager->updatePlayerVisibility($player);
        $essPlayerManager->sendLobbyScoreBoard($player);

        $this->getPlugin()->getFeaturesManager()->getTokens()->spawnSecrets($player);
    }

    private function sendTitle(Player $player): void
    {
        $ess = $this->getNGEssentials();
        $titles = $ess->getServerData()->getArray(ServerData::TITLES);

        if (!$ess->getPlayerData()->getBool($player, PlayerData::OFFICIAL_ADDRESS)) {
            $sendTitle = static function (Player $player): void {
                $player->sendTitle(TextFormat::RED . "Please use ", TextFormat::AQUA . "play.nethergames.org" . TextFormat::EOL . TextFormat::RED . " to play on this server!", -1, 40, -1);
            };
        } elseif (count($titles) > 0) {
            $title = $titles[array_rand($titles)];

            $sendTitle = static function (Player $player) use ($title): void {
                $player->sendTitle($title['title'], $title['subtitle'], $title['fadeIn'], $title['duration'], $title['fadeOut']);
            };
        } else {
            return;
        }

        $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($player, $sendTitle): void {
            if ($player->isConnected()) {
                $sendTitle($player);
            }
        }), 20 * 3);
    }

    public function onToggleFlight(PlayerToggleFlightEvent $event): void
    {
        $player = $event->getPlayer();
        $featuresManager = $this->getPlugin()->getFeaturesManager();
        if ($featuresManager->getParkour()->isPlaying($player) || $featuresManager->isUsingRange($player)) {
            $event->cancel();
            Translator::sendMessage($player, "global.nothere", Translator::TYPE_ERROR);
        }
    }

    public function onToggleGlide(PlayerToggleGlideEvent $event): void
    {
        $player = $event->getPlayer();

        $featuresManager = $this->getPlugin()->getFeaturesManager();
        $isTracking = $this->getNGEssentials()->getPlayerData()->getString($player, PlayerData::TRACK) !== "";
        if ($isTracking || $featuresManager->getParkour()->isPlaying($player) || $featuresManager->isUsingRange($player)) {
            $event->cancel();
            Translator::sendMessage($player, "global.nothere", Translator::TYPE_ERROR);
        } elseif ($event->isGliding()) {
            $player->getInventory()->setItem(Items::ELYTRA_FIREWORK_INDEX, Items::getElytraFirework());
        } else {
            $player->getInventory()->setItem(Items::ELYTRA_FIREWORK_INDEX, Items::getZoneTeleporter());
        }
    }

    public function onCommandPreProcess(CommandEvent $event): void
    {
        $player = $event->getSender();
        if (!$player instanceof Player) {
            return;
        }

        if ($player->hasPermission(Permissions::RANK_ADMIN)) {
            return;
        }

        $featuresManager = $this->getPlugin()->getFeaturesManager();
        if ($featuresManager->getParkour()->isPlaying($player) || $featuresManager->isUsingRange($player)) {
            $event->cancel();
            Translator::sendMessage($player, "command.nothere", Translator::TYPE_ERROR);
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @priority LOWEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof NPC) {
            $event->cancel();
        }
    }

    /**
     * @param WorldLoadEvent $event
     * @priority LOWEST
     */
    public function onWorldLoad(WorldLoadEvent $event): void
    {
        $world = $event->getWorld();

        $world->setTime(World::TIME_DAY);
        $world->stopTime();
    }

    /**
     * @param Player $player
     * @param Item $item
     *
     * @return bool
     */
    public function onItemInteract(Player $player, Item $item): bool
    {
        $featureManager = $this->getPlugin()->getFeaturesManager();
        $parkour = $featureManager->getParkour();
        $maze = $featureManager->getMaze();

        switch ($item) {
            case Items::getParkourLeave():
                $parkour->stopParkour($player, false);
                $player->sendMessage(TextFormat::GREEN . "You abandoned the parkour attempt.");
                break;
            case Items::getParkourRestart():
                $parkour->resetParkour($player);
                break;
            case Items::getParkourLastCheckpoint():
                $player->teleport($parkour->getCheckPoint($player));
                break;
            case Items::getMazeRestart():
                $player->teleport(new Position(42, 3, -245, $player->getWorld()));
                break;
            case Items::getMazeLeave():
                $player->teleport(new Position(42, 3, -245, $player->getWorld()));
                $maze->stop($player, false, false);
                $player->sendMessage(TextFormat::GREEN . "You abandoned the maze attempt.");
                break;
            case Items::getZoneTeleporter():
                Forms::sendZoneMenu($player);
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * @param PlayerQuitEvent $event
     * @priority NORMAL
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        $this->getPlugin()->getActivityManager()->removeFromActivity($player, true);
        $this->getPlugin()->getLogger()->info("Removing " . $player->getName() . " from remaining activities.");
    }

    /**
     * @param PlayerItemHeldEvent $event
     * @priority NORMAL
     */
    public function onItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Utils::hasClassicUI($player)) {
            $this->onItemInteract($player, $event->getItem());
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     * @priority NORMAL
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($this->onItemInteract($player, $item)) {
            $event->cancel();
        } elseif ($item->equals(Items::getElytraFirework())) {
            $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                if ($player->isConnected() && $player->isGliding()) {
                    $player->getInventory()->setItem(Items::ELYTRA_FIREWORK_INDEX, Items::getElytraFirework());
                } else {
                    throw new CancelTaskException();
                }
            }), 3 * 20);
        }
    }
}