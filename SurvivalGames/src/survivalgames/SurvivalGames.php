<?php

declare(strict_types=1);

namespace survivalgames;

use libminigames\Arena;
use libminigames\Minigame;
use libminigames\utils\Autoloader;
use libminigames\utils\AutoUpgrader;
use libVanilla\features\Feature;
use libVanilla\VanillaPlugin;
use muqsit\invmenu\InvMenuHandler;
use NetherGames\NGEssentials\utils\SkinUtils;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\World;
use survivalgames\commands\DeathmatchCommand;
use survivalgames\commands\SGCommand;
use survivalgames\utils\SGArenaConfig;
use function array_filter;
use function dirname;
use function is_dir;
use function stream_get_contents;
use const DIRECTORY_SEPARATOR;

Autoloader::initAutoloader(dirname(__FILE__, 3) . '/vendor/autoload.php');

class SurvivalGames extends Minigame
{

    /** @var Skin */
    public static Skin $graveyard;
    /** @var SGArenaConfig */
    private SGArenaConfig $arenaConfig;

    public function isStandAloneGame(): bool
    {
        return true;
    }

    public function registerClasses(): void
    {
        $ess = $this->getEssentials();

        $directory = 'skins' . DIRECTORY_SEPARATOR . 'objects' . DIRECTORY_SEPARATOR . 'graveyard' . DIRECTORY_SEPARATOR;
        $geometry = $ess->getResource($directory . 'tombstone.json');

        // Save graveyard cached skin to be reused..? todo: use our own library for it
        self::$graveyard = new Skin("geometry.tombstone",
            SkinUtils::getTextureFromResources($directory . 'tombstone.png'),
            "",
            "geometry.tombstone",
            stream_get_contents($geometry)
        );

        $arenaConfig = new Config($this->getDataFolder() . "arenas.yml", Config::YAML, ["arenas" => []]);
        $arenaConfig->save();

        InvMenuHandler::register($this);

        foreach ($this->getRequiredFeatures() as $feature) {
            $feature->register($this);
        }

        AutoUpgrader::getInstance();

        $this->arenaConfig = new SGArenaConfig($arenaConfig);

        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register(SGCommand::class, new SGCommand($this));
        $commandMap->register(DeathmatchCommand::class, new DeathmatchCommand($this));

        $this->getServer()->getPluginManager()->registerEvents(new SGListener($this), $this);
    }

    public function getModes(): array
    {
        return ['Solo'];
    }

    /**
     * @return array<Feature>
     */
    public function getRequiredFeatures(): array
    {
        return [
            VanillaPlugin::FISHING_ROD(),
            VanillaPlugin::CROSSBOW(),
            VanillaPlugin::SHIELD(),
            VanillaPlugin::TRIDENTS(),
        ];
    }

    public function generateNewArena(int $modeId, bool $privateGame = false): Arena
    {
        return new SGArena($this, $modeId, $this->mapsPlayed++, $privateGame);
    }

    /**
     * @param World $world
     *
     * @return SGArena|null
     */
    public function getArenaByWorld(World $world): ?Arena
    {
        /** @var SGArena|null $arena */
        $arena = parent::getArenaByWorld($world);

        return $arena;
    }

    /**
     * @param Player $player
     *
     * @return SGArena|null
     */
    public function getArena(Player $player): ?Arena
    {
        /** @var SGArena|null $SGArena */
        $SGArena = parent::getArena($player);

        return $SGArena;
    }

    public function getMaps(bool $onlyEnabled): array
    {
        return array_filter(
            array: $this->getArenaConfig()->getMaps($onlyEnabled),
            callback: fn(string $mapName) => preg_match(
                    pattern: "/^([a-zA-Z]-)?SG-([a-zA-Z0-9]+)/",
                    subject: $mapName
                ) && is_dir("{$this->getDataFolder()}/arenas/$mapName"),
        );
    }

    public function getArenaConfig(): SGArenaConfig
    {
        return $this->arenaConfig;
    }
}