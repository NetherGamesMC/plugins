<?php

declare(strict_types=1);

namespace lobby\features\maze;

use lobby\entity\custom\IconMarker;
use lobby\features\activity\ActivityIntent;
use lobby\features\secret\SecretData;
use lobby\Lobby;
use lobby\utils\Items;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\Listener;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class Maze implements Listener
{
    /** @var int */
    public const DISTANCE_TRAVELED = 300;
    public const TOKEN_SPAWN_LOCATION = [42.5, 3, -272.5];

    /** @var int[] */
    private array $mazeData = [];

    /** @var Position[] */
    private array $previousBlock = [];

    public function __construct(Lobby $lobby)
    {
        $world = $lobby->getServer()->getWorldManager()->getDefaultWorld();

        $lobby->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($lobby, $world): void {
            foreach ($world->getPlayers() as $player) {
                if ($this->isPlaying($player)) {
                    if ($player->getAllowFlight() === true || $player->isFlying()) {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                    }
                }
            }

            $axis = new AxisAlignedBB(39, 0, -256, 45, 48, -143);
            $box = new AxisAlignedBB(39, 3, -250, 45, 22, -247);

            $playersInside = array_filter($lobby->getServer()->getWorldManager()->getDefaultWorld()->getNearbyEntities($box), static function (Entity $player): bool {
                return $player instanceof Player;
            });

            $playersOnStairCase = array_filter($lobby->getServer()->getWorldManager()->getDefaultWorld()->getNearbyEntities($axis), static function (Entity $player): bool {
                return $player instanceof Player && PlayerUtils::hasUnlockedToken($player, SecretData::MAZE);
            });

            foreach ($playersInside as $player) {
                $this->start($player);
            }

            /** @var Player $player */
            foreach ($playersOnStairCase as $player) {
                if ($player->getPosition()->getY() <= 15) {
                    $player->teleport(new Position(42.5, 42, -157.5, $player->getWorld()));
                }

                $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 30, 1));
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 30, 10));
            }
        }), 10);
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function isPlaying(Player $player): bool
    {
        return isset($this->mazeData[$player->getName()]);
    }

    public function start(Player $player): void
    {
        if ($this->isPlaying($player)) {
            return;
        }

        $this->mazeData[$player->getName()] = 0;
        $this->previousBlock[$player->getName()] = $player->getPosition();

        $maze = $this;
        $intent = new ActivityIntent(
            player: $player->getXuid(),
            onExit: static function (bool $isDisconnect) use ($player, $maze) {
                $maze->stop($player, false, $isDisconnect);
            });

        Lobby::getInstance()->getActivityManager()->startActivity($intent);

        $petsManager = NGEssentials::getInstance()->getPlayerManager()->getWorldFeatures()->getManager()->getPetsManager();
        $petsManager->removePet($player);

        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->setGamemode(GameMode::ADVENTURE);

        Items::setMazeInventory($player);
        $player->sendMessage(TextFormat::GREEN . "You have started the maze trials.");
    }

    public function stop(Player $player, bool $complete, bool $disconnect): void
    {
        if (!$this->isPlaying($player)) {
            return;
        }

        if (!$disconnect) {
            if ($complete) {
                $traveled = $this->mazeData[$player->getName()];
                if ($traveled < self::DISTANCE_TRAVELED) {
                    $player->sendMessage(TextFormat::RED . "Nice try, please do not cheat while attempting the maze trials.");
                    $player->teleport(new Position(42, 4, -247, $player->getWorld()));

                    return;
                }

                $player->sendMessage(TextFormat::GREEN . "You have completed the maze");
                [$x, $y, $z] = self::TOKEN_SPAWN_LOCATION;
                $location = new Location($x, $y, $z, $player->getWorld(), 0, 0);
                $token = new IconMarker($location, SecretData::MAZE, true);
                $token->addTo($player);
                $token->spawnTo($player);
            }

            Items::setLobbyInventory($player);

            $playerData = NGEssentials::getInstance()->getPlayerData();

            if ($player->hasPermission(Permissions::RANK_ULTRA) && $playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK)) {
                $player->setAllowFlight(true);
            }
        }

        unset($this->mazeData[$player->getName()]);

        Lobby::getInstance()->getActivityManager()->endActivity($player);
    }

    /**
     * @param Player $player
     *
     * @return void
     */
    public function addBlockTraveled(Player $player): void
    {
        $position = $this->previousBlock[$player->getName()];
        if ($position->equals($player->getPosition())) {
            return;
        }

        ++$this->mazeData[$player->getName()];
        $this->previousBlock[$player->getName()] = $player->getPosition();
    }
}