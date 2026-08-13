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

namespace NetherGames\NGEssentials\player\cosmetics;

use Exception;
use JsonException;
use libforms\elements\ImageButton;
use libforms\FormManager;
use libforms\SimpleForm;
use NetherGames\NGEssentials\player\cosmetics\tasks\CosmeticTask;
use NetherGames\NGEssentials\player\cosmetics\types\armor\ArmorCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\armor\BootsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\armor\ChestplatesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\armor\HelmetsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\armor\LeggingsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\AttachablesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CapesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use NetherGames\NGEssentials\player\cosmetics\types\effect\BedBreakEffectsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\effect\KillEffectsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\effect\WinEffectsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\BedsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\cage\SoloCagesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\cage\TeamCagesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\FlagsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\KnifesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\game\ShopkeepersCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\particle\ArrowTrails;
use NetherGames\NGEssentials\player\cosmetics\types\particle\ParticlesCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\particle\Trails;
use NetherGames\NGEssentials\player\cosmetics\types\particle\WingsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\PetsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\sound\BedBreakSoundsCosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\sound\KillSoundsCosmetic;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\utils\PlayerBaseClass;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\RegistryTrait;
use RuntimeException;
use function array_filter;
use function array_key_first;
use function array_map;
use function array_rand;
use function array_reduce;
use function array_unique;
use function count;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function ksort;
use function log;
use function mt_getrandmax;
use function mt_rand;
use function pow;
use function shuffle;
use function sort;
use function unserialize;
use const JSON_THROW_ON_ERROR;
use const SORT_NUMERIC;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static Trails TRAILS()
 * @method static ArrowTrails ARROW_TRAILS()
 * @method static KillEffectsCosmetic KILL_EFFECTS()
 * @method static WinEffectsCosmetic WIN_EFFECTS()
 * @method static ParticlesCosmetic PARTICLES()
 * @method static HelmetsCosmetic HELMETS()
 * @method static ChestplatesCosmetic CHESTPLATES()
 * @method static LeggingsCosmetic LEGGINGS()
 * @method static BootsCosmetic BOOTS()
 * @method static KnifesCosmetic KNIFES()
 * @method static WingsCosmetic WINGS()
 * @method static BedBreakEffectsCosmetic BED_BREAK_EFFECTS()
 * @method static SoloCagesCosmetic SOLO_CAGES()
 * @method static CapesCosmetic CAPES()
 * @method static KillSoundsCosmetic KILL_SOUNDS()
 * @method static BedBreakSoundsCosmetic BED_BREAK_SOUNDS()
 * @method static ShopkeepersCosmetic SHOPKEEPERS()
 * @method static FlagsCosmetic FLAGS()
 * @method static TeamCagesCosmetic TEAM_CAGES()
 * @method static PetsCosmetic PETS()
 * @method static BedsCosmetic BEDS()
 * @method static AttachablesCosmetic ATTACHABLES()
 */
class CosmeticHandler extends PlayerBaseClass
{
    use RegistryTrait;

    private const VERSION_KEY = -1;
    private const CURRENT_VERSION = 2;

    private const MENU_ICON = 0;
    private const MENU_COSMETICS = 1;

    /**
     * @var array<string|int, array{
     *     0: string,
     *     1: array<string|int, mixed>
     * }|Cosmetic>
     */
    private array $cosmeticMenu;

    private static bool $inSetup = false;

    public function __construct(PlayerManager $manager)
    {
        parent::__construct($manager);

        self::$inSetup = true;
        self::checkInit();
        self::$inSetup = false;

        self::register("trails", new Trails(1, $this));
        self::register("arrow_trails", new ArrowTrails(2, $this));
        self::register("kill_effects", new KillEffectsCosmetic(3, $this));
        self::register("win_effects", new WinEffectsCosmetic(4, $this));
        self::register("particles", new ParticlesCosmetic(5, $this));
        self::register("helmets", new HelmetsCosmetic(6, $this));
        self::register("chestplates", new ChestplatesCosmetic(7, $this));
        self::register("leggings", new LeggingsCosmetic(8, $this));
        self::register("boots", new BootsCosmetic(9, $this));
        self::register("knifes", new KnifesCosmetic(10, $this));
        self::register("wings", new WingsCosmetic(11, $this));
        self::register("bed_break_effects", new BedBreakEffectsCosmetic(12, $this));
        self::register("solo_cages", new SoloCagesCosmetic(13, $this));
        self::register("capes", new CapesCosmetic(14, $this));
        self::register("kill_sounds", new KillSoundsCosmetic(15, $this));
        self::register("bed_break_sounds", new BedBreakSoundsCosmetic(16, $this));
        self::register("shopkeepers", new ShopkeepersCosmetic(17, $this));
        self::register("flags", new FlagsCosmetic(18, $this));
        self::register("team_cages", new TeamCagesCosmetic(19, $this));
        self::register("pets", new PetsCosmetic(20, $this));
        self::register("beds", new BedsCosmetic(21, $this));
        self::register("attachables", new AttachablesCosmetic(22, $this));

        if (($serverManager = ($plugin = $manager->getPlugin())->getServerManager())->getServerType() !== ServerManager::REPLAY && !$serverManager->isMMOGame()) {
            if ($serverManager->enableLobbyHandling()) {
                $plugin->getScheduler()->scheduleRepeatingTask(new CosmeticTask($this), 1);
            }

            $plugin->getServer()->getPluginManager()->registerEvents(new CosmeticListener($this), $plugin);
        }

        $this->cosmeticMenu = [
            "Particles" => [
                self::MENU_ICON => "textures/ui/ng/tabs/particles",
                self::MENU_COSMETICS => [
                    self::TRAILS(),
                    self::ARROW_TRAILS(),
                    self::PARTICLES(),
                    self::WINGS()
                ]
            ],
            "Celebration Effects/Sounds" => [
                self::MENU_ICON => "textures/ui/ng/tabs/fx",
                self::MENU_COSMETICS => [
                    self::KILL_EFFECTS(),
                    self::WIN_EFFECTS(),
                    self::KILL_SOUNDS(),
                ]
            ],
            "Armor" => [
                self::MENU_ICON => "textures/ui/ng/tabs/armor",
                self::MENU_COSMETICS => $this->getArmorCosmetics(),
            ],
            "Game" => [
                self::MENU_ICON => "textures/ui/ng/tabs/game",
                self::MENU_COSMETICS => [
                    "Murder Mystery" => [
                        self::MENU_ICON => 'textures/ui/murdermystery',
                        self::MENU_COSMETICS => [
                            self::KNIFES()
                        ]
                    ],
                    "Bedwars" => [
                        self::MENU_ICON => "textures/ui/bedwars",
                        self::MENU_COSMETICS => [
                            self::SHOPKEEPERS(),
                            self::BED_BREAK_EFFECTS(),
                            self::BED_BREAK_SOUNDS(),
                            self::BEDS()
                        ]
                    ],
                    "Conquests" => [
                        self::MENU_ICON => "textures/ui/conquests",
                        self::MENU_COSMETICS => [
                            self::SHOPKEEPERS(),
                            self::FLAGS()
                        ]
                    ],
                ]
            ],
            "Cages" => [
                self::MENU_ICON => "textures/ui/ng/tabs/cagesolo",
                self::MENU_COSMETICS => [
                    self::SOLO_CAGES(),
                    self::TEAM_CAGES()
                ]
            ],
            self::CAPES(),
            self::PETS(),
            self::ATTACHABLES(),
        ];

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            MySQLCredentials::executeSelect("cosmetics.load", ["status" => 1], function (array $rows): void {
                $cosmeticEntries = array_map(static fn(array $row): CosmeticEntry => CosmeticEntry::fromArray($row), $rows);

                foreach (self::getAll() as $cosmetic) {
                    $cosmetic->setEntries($cosmeticEntries);
                }
            });
        }), 30 * 60 * 20); // 30 minutes
    }

    /**
     * @throws Exception
     */
    protected static function setup(): void
    {
        if (!self::$inSetup) {
            throw new Exception("This method should not be called");
        }
    }

    private static function register(string $registryName, Cosmetic $cosmetic): void
    {
        self::_registryRegister($registryName, $cosmetic);
    }

    /**
     * @return Cosmetic[]
     */
    public static function getAll(): array
    {
        return self::_registryGetAll();
    }

    public function processLoading(string $data): array
    {
        try {
            $array = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            try {
                $array = unserialize($data);

                if (!is_array($array)) {
                    $array = [];
                }
            } catch (Exception $e) {
                $array = [];
            }
        }

        return $this->processVersion($array);
    }

    private function processVersion(array $array): array
    {
        while (($currentVersion = ($array[self::VERSION_KEY] ??= 0)) < self::CURRENT_VERSION) {
            $array = match ($currentVersion) {
                0 => $this->upgrade0To1($array),
                1 => $this->upgrade1To2($array),
                default => throw new RuntimeException("Unknown version: $currentVersion")
            };
        }

        return $array;
    }

    private function upgrade0To1(array $array): array
    {
        $array[self::VERSION_KEY] = 1;

        $soloCages = $array[13] ?? [];
        $doubleCages = $array[19] ?? [];

        $replace = function (array $search, int $replacement) use (&$soloCages, &$doubleCages): void {
            if (array_intersect($search, $soloCages)) {
                $soloCages = array_diff($soloCages, $search);

                if (!in_array($replacement, $doubleCages, true)) {
                    $doubleCages[] = $replacement;
                }
            }
        };

        $replace([121, 122], 101);
        $replace([125, 126], 102);
        $replace([133, 134], 103);
        $replace([137, 138], 104);
        $replace([139, 140], 105);
        $replace([142, 143], 106);

        sort($soloCages);
        sort($doubleCages);

        $array[13] = $soloCages;
        $array[19] = array_unique($doubleCages);

        return $array;
    }

    private function upgrade1To2(array $array): array
    {
        $array[self::VERSION_KEY] = 2;

        if (in_array(126, $array[14] ?? [], true)) {
            $array[14][] = 132;
            sort($array[14]);
        }

        return $array;
    }

    public function shouldGiveCrateKey(Player $player): bool
    {
        $cosmeticsAmount = $this->getCosmeticsAmount();
        if ($cosmeticsAmount === 0) {
            return false;
        }

        $availableCrateKeys = $this->getPlugin()->getPlayerData()->getInt($player, PlayerData::KEYS);
        $ownedCosmeticAmount = $cosmeticsAmount - $this->getAvailableCosmeticsAmount($player) + $availableCrateKeys;
        $percentage = $ownedCosmeticAmount / $cosmeticsAmount;

        return match (true) {
            $percentage === 0.0 => true,
            $percentage < 0.2 => (mt_rand() / mt_getrandmax()) < pow(-log($percentage + 0.05, 50), 3),
            $percentage >= 1.0 => false,
            default => (mt_rand() / mt_getrandmax()) < (-0.05 * $percentage) + 0.05
        };
    }

    private function getCosmeticsAmount(): int
    {
        return array_reduce(self::getAll(), static fn(int $carry, Cosmetic $cosmetic): int => $carry + count($cosmetic->getCrateIds()), 0);
    }

    private function getAvailableCosmeticsAmount(Player $player): int
    {
        return array_reduce(self::getAll(), static fn(int $carry, Cosmetic $cosmetic): int => $carry + count($cosmetic->getAvailableCrateIds($player)), 0);
    }

    public function getAvailableCrateCosmetic(Player $player): ?CosmeticEntry
    {
        $cosmetics = self::getAll();

        shuffle($cosmetics);

        foreach ($cosmetics as $cosmetic) {
            if (count($cosmeticIds = $cosmetic->getAvailableCrateIds($player)) > 0) {
                return $cosmetic->getEntry($cosmeticIds[array_rand($cosmeticIds)]) ?? throw new RuntimeException("Cosmetic entry not found");
            }
        }

        return null;
    }

    public static function getCosmeticById(int $id): ?Cosmetic
    {
        foreach (self::getAll() as $cosmetic) {
            if ($cosmetic->getSaveId() === $id) {
                return $cosmetic;
            }
        }

        return null;
    }

    public function sendForm(Player $player, ?callable $onBack = null, ?string $title = null, ?array $data = null, ?Cosmetic $cosmetic = null): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $data ??= $this->cosmeticMenu;
            $cosmetic ??= self::CAPES();

            if ($cosmetic->showSkin()) {
                $form->setType(SimpleForm::FORM_TABS_WITH_SKIN);
                $form->setContent($player->getUniqueId()->toString());
            } else {
                $form->setType(SimpleForm::FORM_TABS);
            }
            $form->setTitle($title ?? "Cosmetics");
            $form->setBackClosure($onBack);

            $goBack = function (Player $player) use ($onBack): void {
                $this->sendForm($player, $onBack);
            };

            $form->addButton(new ImageButton(SimpleForm::BUTTON_TAB . "Home", ImageButton::IMAGE_TYPE_PATH, "textures/ui/ng/tabs/home", function (Player $player): void {
                $this->sendForm($player);
            }));

            foreach ($data as $category => $value) {
                if ($value instanceof Cosmetic) {
                    $form->addButton($value->getButton($player, function (Player $player) use ($onBack, $data, $value): void {
                        $this->sendForm($player, $onBack, $value->getName(), $data, $value);
                    }));
                } else {
                    $form->addButton(new ImageButton(SimpleForm::BUTTON_TAB . $category, ImageButton::IMAGE_TYPE_PATH, $value[self::MENU_ICON], function (Player $player) use ($value, $goBack): void {
                        $data = $value[self::MENU_COSMETICS];
                        $cosmetic = $data[array_key_first($data)];

                        if (!$cosmetic instanceof Cosmetic) {
                            $cosmeticData = $cosmetic[self::MENU_COSMETICS];
                            $cosmetic = $cosmeticData[array_key_first($cosmeticData)];
                        }

                        $this->sendForm($player, $goBack, $cosmetic->getName(), $data, $cosmetic);
                    }));
                }
            }

            $cosmetic->addCosmeticsToForm($form, $player, function (Player $player) use ($goBack, $title, $data, $cosmetic): void {
                $this->sendForm($player, $goBack, $title, $data, $cosmetic);
            });

            $form->sendForm();
        }
    }

    /**
     * @return array<ArmorCosmetic>
     */
    public function getArmorCosmetics(): array
    {
        return array_filter(self::getAll(), static fn(Cosmetic $cosmetic): bool => $cosmetic instanceof ArmorCosmetic);
    }

    public function equipArmorCosmetics(Player $player): void
    {
        foreach ($this->getArmorCosmetics() as $cosmetic) {
            $cosmetic->equip($player);
        }
    }

    public function removeArmorCosmetics(Player $player): void
    {
        foreach ($this->getArmorCosmetics() as $cosmetic) {
            $cosmetic->remove($player);
        }
    }

    /**
     * @param (string|int)[] $playerXuids
     */
    public function givePlayersCosmetic(array $playerXuids, int $cosmeticType, int $cosmeticId): void
    {
        foreach ($playerXuids as $playerXuid) {
            MySQLCredentials::executeSelect('player.get_cosmetics', ['player_xuid' => (string)$playerXuid], function (array $rows) use ($playerXuid, $cosmeticType, $cosmeticId): void {
                $worked = false;

                foreach ($rows as $row) {
                    $cosmetics = $this->processLoading($row['cosmetics']);

                    if (!in_array($cosmeticId, $cosmetics[$cosmeticType] ?? [], true)) {
                        $cosmetics[$cosmeticType][] = $cosmeticId;
                    }

                    sort($cosmetics[$cosmeticType]);
                    ksort($cosmetics, SORT_NUMERIC);

                    MySQLCredentials::executeChange('player.update_cosmetics', ['cosmetics' => json_encode($cosmetics, JSON_THROW_ON_ERROR), 'player_xuid' => (string)$playerXuid], function () use ($playerXuid): void {
                        $this->getPlugin()->getLogger()->info('Successfully updated cosmetics for ' . $playerXuid);
                    });

                    $worked = true;
                }

                if (!$worked) {
                    $this->getPlugin()->getLogger()->info('Failed to update cosmetics for ' . $playerXuid);
                }
            });
        }
    }

    /**
     * @param (string|int)[] $playerXuids
     */
    public function removePlayersCosmetic(array $playerXuids, int $cosmeticType, int $cosmeticId): void
    {
        foreach ($playerXuids as $playerXuid) {
            MySQLCredentials::executeSelect('player.get_cosmetics', ['player_xuid' => (string)$playerXuid], function (array $rows) use ($playerXuid, $cosmeticType, $cosmeticId): void {
                $worked = false;

                foreach ($rows as $row) {
                    $cosmetics = $this->processLoading($row['cosmetics']);

                    if (in_array($cosmeticId, $cosmetics[$cosmeticType] ?? [], true)) {
                        $cosmetics[$cosmeticType] = array_filter($cosmetics[$cosmeticType], static fn(int $id): bool => $id !== $cosmeticId);
                    }

                    if (empty($cosmetics[$cosmeticType])) {
                        unset($cosmetics[$cosmeticType]);
                    } else {
                        sort($cosmetics[$cosmeticType]);
                    }

                    ksort($cosmetics, SORT_NUMERIC);

                    MySQLCredentials::executeChange('player.update_cosmetics', ['cosmetics' => json_encode($cosmetics, JSON_THROW_ON_ERROR), 'player_xuid' => (string)$playerXuid], function () use ($playerXuid): void {
                        $this->getPlugin()->getLogger()->info('Successfully updated cosmetics for ' . $playerXuid);
                    });

                    $worked = true;
                }

                if (!$worked) {
                    $this->getPlugin()->getLogger()->info('Failed to update cosmetics for ' . $playerXuid);
                }
            });
        }
    }
}