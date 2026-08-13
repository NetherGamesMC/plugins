<?php
declare(strict_types=1);

namespace uhc\game\scenario\base;

use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\RegistryTrait;
use uhc\game\scenario\CatEyes;
use uhc\game\scenario\CutClean;
use uhc\game\scenario\DoubleOrNothing;
use uhc\game\scenario\Fireless;
use uhc\game\scenario\FlowerPower;
use uhc\game\scenario\GoldenTrees;
use uhc\game\scenario\HasteyBoys;
use uhc\game\scenario\NoFall;
use uhc\game\scenario\Superheroes;
use uhc\game\scenario\Timber;

/**
 * Unlike PocketMine, I manually write these, auto generation is overrated
 *
 * @method static Scenario CATEYES()
 * @method static Scenario CUTCLEAN()
 * @method static Scenario DIAMONDLESS()
 * @method static Scenario DOUBLE_OR_NOTHING()
 * @method static Scenario FIRELESS()
 * @method static Scenario FLOWER_POWER()
 * @method static Scenario GOLDEN_TREES()
 * @method static Scenario HASTEY_BOYS()
 * @method static Scenario MONSTERS_INC()
 * @method static Scenario NO_FALL()
 * @method static Scenario SUPERHEROES()
 * @method static Scenario TIMBER()
 */
class ScenarioRegistry
{
    use RegistryTrait;

    public static function fromString(string $name): Scenario
    {
        /** @var Scenario $scenario */
        $scenario = self::_registryFromString(strtolower($name));
        return $scenario;
    }

    /**
     * @return Scenario[]
     */
    public static function getAll(): array
    {
        /** @var Scenario[] $scenarios */
        $scenarios = self::_registryGetAll();
        return $scenarios;
    }

    protected static function setup(): void
    {
        self::register(new CatEyes("cateyes", CustomIcon::CAT_EYES . "Cat's Eyes",
            "All players are given Night Vision at the start of the game."));
        self::register(new CutClean("cutclean", CustomIcon::CUTCLEAN . "CutClean", "", true));
        self::register(new DoubleOrNothing("double_or_nothing", CustomIcon::DOUBLE_OR_NOTHING . "Double or Nothing",
            "On mine of iron, diamond, emerald or gold ore you have a 50% chance of 2 of the ore dropping or no ores dropping."));
        self::register(new Fireless("fireless", CustomIcon::FIRELESS . "Fireless",
            "Players are unable to burn."));
        self::register(new FlowerPower("flower_power", CustomIcon::FLOWER_POWER . "Flower Power",
            "When a player breaks any random flower, there is a chance that the flower will drop a random item (excluding notch apples)."));
        self::register(new GoldenTrees("golden_trees", CustomIcon::GOLDEN_TREE . "Golden Trees",
            "1%% chance of a golden apple falling from a tree"));
        self::register(new HasteyBoys("hastey_boys", CustomIcon::HASTE . "Hastey Boys",
            "All tools are enchanted with efficiency III and unbreaking I."));
        self::register(new NoFall("no_fall", CustomIcon::FEATHER_FALLING . "NoFall",
            "Players are unable to take fall damage."));
        self::register(new Superheroes("superheroes", CustomIcon::SUPERHEROES . "Superheroes",
            "Each player will gain a special ability. The powers are speed 1, strength 1, resistance 2, invisibility, and jump boost 4."));
        self::register(new Timber("timber", CustomIcon::TIMBER . "Timber", "", true));
    }

    public static function register(Scenario $scenario): void
    {
        self::_registryRegister($scenario->getName(), $scenario);
    }
}
