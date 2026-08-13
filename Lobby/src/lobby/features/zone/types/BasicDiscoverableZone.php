<?php
declare(strict_types=1);

namespace lobby\features\zone\types;

use lobby\utils\BaseTrait;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\worldfeatures\zones\Zone;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\world\World;

class BasicDiscoverableZone extends Zone
{
    use BaseTrait;

    public function __construct(private string $name, private ?Location $teleportLocation, AxisAlignedBB $alignedBB, World $world)
    {
        parent::__construct($alignedBB, $world);
    }

    public function leave(Player $player): void
    {
    }

    public function enter(Player $player): void
    {
        $discovered = NGEssentials::getInstance()->getPlayerData()->getArray($player, PlayerData::LOBBY_DISCOVERED_ZONES);
        if (in_array($this->name, $discovered, true)) {
            $player->sendTitle("§aYou've entered", "§c" . $this->name);
        } else {
            $player->sendTitle("§c§lDiscovered", "§a" . $this->name);
            PlayerUtils::playSound($player, "random.orb", 1);
            $discovered[] = $this->name;
            $this->getNGEssentials()->getPlayerData()->setValue($player, PlayerData::LOBBY_DISCOVERED_ZONES, $discovered);
        }
    }

    /**
     * @return Location
     */
    public function getTeleportLocation(): Location
    {
        return $this->teleportLocation;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}