<?php
declare(strict_types=1);

namespace uhc\game;

use libminigames\Team;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\utils\PlayerCache;
use uhc\utils\StatsData;
use function count;

class UHCTeam extends Team
{
    /** @var string[] */
    private array $xuidCache = [];
    /** @var PlayerCache[] */
    private array $gameCache = [];

    public function reconnectPlayer(NGPlayer $player): void
    {
        /** @var UHCArena $arena */
        $arena = $this->getArena();
        $this->addPlayer($player);

        $player->setGamemode(GameMode::SURVIVAL);
        $player->setEnergized();

        $inventoryCache = $this->gameCache[$player->getXuid()];
        $player->setHealth($inventoryCache->getHealth());
        $player->getXpManager()->setCurrentTotalXp($inventoryCache->getXp());
        $player->teleport($inventoryCache->getLocation());
        $player->getInventory()->setContents($inventoryCache->getInventoryContents());
        $player->getArmorInventory()->setContents($inventoryCache->getArmorContents());
        foreach ($inventoryCache->getEffects() as $effect) {
            $player->getEffects()->add($effect);
        }
        unset($this->gameCache[$player->getXuid()]);

        $player->toggleGameRule("showcoordinates", true);

        $arena->getScoreboard()->addPlayer($player);
        $this->sendScoreboard($player);
    }

    public function sendScoreboard(?Player $player = null): void
    {
        /** @var UHCArena $arena */
        $arena = $this->getArena();
        $statsData = $arena->getStatsData();

        $arena->getScoreboard()->setLines($player === null ? $this->getPlayers() : [$player], [
            8 => "",
            UHCArena::LINE_TIMER => CustomIcon::HOURGLASS,
            6 => "",
            UHCArena::LINE_ALIVE => CustomIcon::STEVE_HEAD . TextFormat::GREEN . count($arena->getAlivePlayers()),
            UHCArena::LINE_KILLS => CustomIcon::KILLS . TextFormat::GREEN . ($player === null ? 0 : $statsData->getValue($player, StatsData::UHC_KILLS)),
            UHCArena::LINE_BORDER => CustomIcon::BORDER . TextFormat::GREEN . $arena->getBorder()->getSize(),
            2 => "",
            1 => CustomIcon::NETHERGAMES . TextFormat::GOLD . "ngmc.co"
        ]);
    }

    public function removeFromXuidCache(string $xuid): void
    {
        $key = array_search($xuid, $this->xuidCache, true);
        if ($key !== false) {
            unset($this->xuidCache[$key]);
        }
    }

    public function removePlayer(Player $player, bool $teamChange = false): void
    {
        parent::removePlayer($player, $teamChange);

        if ($this->getArena()->isRunning() && !$this->getArena()->isSpectator($player)) {
            $this->gameCache[$player->getXuid()] = new PlayerCache(
                $player->getXuid(), $player->getLocation(), $player->getHealth(),
                $player->getXpManager()->getCurrentTotalXp(), $player->getArmorInventory()->getContents(),
                $player->getInventory()->getContents(), $player->getEffects()->all()
            );
            $this->getArena()->getScoreboard()->setLine($this->getArena()->getPlayers(), UHCArena::LINE_ALIVE, CustomIcon::STEVE_HEAD . "§a" . count($this->getArena()->getAlivePlayers()));
        }
    }

    public function addToXuidCache(string $xuid): void
    {
        if (!in_array($xuid, $this->xuidCache, true)) {
            $this->xuidCache[] = $xuid;
        }
    }

    /**
     * @return string[]
     */
    public function getXuidCache(): array
    {
        return $this->xuidCache;
    }
}