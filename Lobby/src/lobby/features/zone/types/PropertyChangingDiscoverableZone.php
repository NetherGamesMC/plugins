<?php
declare(strict_types=1);

namespace lobby\features\zone\types;

use Closure;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\permissions\RankManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\World;

class PropertyChangingDiscoverableZone extends BasicDiscoverableZone
{
    /**
     * @param bool $flightAllowed
     * @param bool $hideOthers
     * @param Closure $onExit
     * @param string $name
     * @param Location|null $teleportLocation
     * @param AxisAlignedBB $alignedBB
     * @param World $world
     *
     * @phpstan-param Closure(Player) : void $onExit
     */
    public function __construct(
        private bool    $flightAllowed,
        private bool    $hideOthers,
        private Closure $onExit,
        string          $name,
        ?Location       $teleportLocation,
        AxisAlignedBB   $alignedBB,
        World           $world)
    {
        parent::__construct($name, $teleportLocation, $alignedBB, $world);
    }

    public function enter(Player $player): void
    {
        if (!$player->isConnected()) {
            return;
        }

        parent::enter($player);

        if ($this->hideOthers) {
            foreach ($this->getInsidePlayers() as $insidePlayer) {
                $player->hidePlayer($insidePlayer);
            }
        }

        $player->setFlying($this->flightAllowed);
        $player->setAllowFlight($this->flightAllowed);

        $player->sendJukeboxPopup(Translator::getTranslationPlayer($player, "zone.fly.disabled", Translator::TYPE_ERROR));
    }

    public function leave(Player $player): void
    {
        if (!$player->isConnected()) {
            return;
        }

        parent::leave($player);

        if ($this->hideOthers) {
            foreach ($this->getInsidePlayers() as $insidePlayer) {
                $player->showPlayer($insidePlayer);
            }
        }

        $playerData = $this->getNGEssentials()->getPlayerData();
        if ($playerData->getString($player, PlayerData::SELECTED_RANK) !== RankManager::NO_RANK && !$playerData->getBool($player, PlayerData::NICK) && $player->hasPermission(Permissions::RANK_ULTRA)) {
            $player->setAllowFlight(true);
        }

        ($this->onExit)($player);
    }
}