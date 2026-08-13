<?php
declare(strict_types=1);

namespace libVanilla\entity\registry;

use libVanilla\entity\hostile\Creeper;
use libVanilla\entity\hostile\Ghast;
use libVanilla\entity\hostile\Skeleton;
use libVanilla\entity\hostile\Zombie;
use libVanilla\entity\neutral\Enderman;
use libVanilla\entity\neutral\IronGolem;
use libVanilla\entity\neutral\SnowGolem;
use libVanilla\entity\neutral\Spider;
use libVanilla\entity\neutral\ZombiePigman;
use libVanilla\entity\passive\Chicken;
use libVanilla\entity\passive\Cow;
use libVanilla\entity\passive\Mooshroom;
use libVanilla\entity\passive\Pig;
use libVanilla\entity\passive\Rabbit;
use libVanilla\entity\passive\Sheep;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\utils\RegistryTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see \pocketmine\utils\RegistryUtils::_generateMethodAnnotations()
 *
 * @method static ActorList CHICKEN()
 * @method static ActorList COW()
 * @method static ActorList CREEPER()
 * @method static ActorList ENDERMAN()
 * @method static ActorList GHAST()
 * @method static ActorList IRON_GOLEM()
 * @method static ActorList MOOSHROOM()
 * @method static ActorList PIG()
 * @method static ActorList RABBIT()
 * @method static ActorList SHEEP()
 * @method static ActorList SKELETON()
 * @method static ActorList SNOW_GOLEM()
 * @method static ActorList SPIDER()
 * @method static ActorList ZOMBIE()
 * @method static ActorList ZOMBIE_PIGMAN()
 */
class ActorRegistry
{
    use RegistryTrait;

    /**
     * @return object[]
     */
    public static function getAll(): array
    {
        return self::_registryGetAll();
    }

    protected static function setup(): void
    {
        self::register("chicken", new ActorList(Chicken::class, "Chicken", EntityIds::CHICKEN));
        self::register("cow", new ActorList(Cow::class, "Cow", EntityIds::COW));
        self::register("creeper", new ActorList(Creeper::class, "Creeper", EntityIds::CREEPER));
        self::register("enderman", new ActorList(Enderman::class, "Enderman", EntityIds::ENDERMAN));
        self::register("ghast", new ActorList(Ghast::class, "Ghast", EntityIds::GHAST));
        self::register("iron_golem", new ActorList(IronGolem::class, "Iron Golem", EntityIds::IRON_GOLEM));
        self::register("mooshroom", new ActorList(Mooshroom::class, "Mooshroom", EntityIds::MOOSHROOM));
        self::register("pig", new ActorList(Pig::class, "Pig", EntityIds::PIG));
        self::register("rabbit", new ActorList(Rabbit::class, "Rabbit", EntityIds::RABBIT));
        self::register("sheep", new ActorList(Sheep::class, "Sheep", EntityIds::SHEEP));
        self::register("skeleton", new ActorList(Skeleton::class, "Skeleton", EntityIds::SKELETON));
        self::register("snow_golem", new ActorList(SnowGolem::class, "Snow Golem", EntityIds::SNOW_GOLEM));
        self::register("spider", new ActorList(Spider::class, "Spider", EntityIds::SPIDER));
        self::register("zombie", new ActorList(Zombie::class, "Zombie", EntityIds::ZOMBIE));
        self::register("zombie_pigman", new ActorList(ZombiePigman::class, "Zombie Pigman", EntityIds::ZOMBIE_PIGMAN));
    }

    protected static function register(string $name, ActorList $mobList): void
    {
        self::_registryRegister($name, $mobList);
    }
}
