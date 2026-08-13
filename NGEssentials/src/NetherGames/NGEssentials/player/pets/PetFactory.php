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

namespace NetherGames\NGEssentials\player\pets;

use NetherGames\NGEssentials\entity\pets\bouncing\MagmaCubePet;
use NetherGames\NGEssentials\entity\pets\bouncing\RabbitPet;
use NetherGames\NGEssentials\entity\pets\bouncing\SlimePet;
use NetherGames\NGEssentials\entity\pets\hovering\ArrowPet;
use NetherGames\NGEssentials\entity\pets\hovering\BatPet;
use NetherGames\NGEssentials\entity\pets\hovering\BeePet;
use NetherGames\NGEssentials\entity\pets\hovering\BlazePet;
use NetherGames\NGEssentials\entity\pets\hovering\EnderCrystalPet;
use NetherGames\NGEssentials\entity\pets\hovering\EnderDragonPet;
use NetherGames\NGEssentials\entity\pets\hovering\GhastPet;
use NetherGames\NGEssentials\entity\pets\hovering\VexPet;
use NetherGames\NGEssentials\entity\pets\hovering\WitherPet;
use NetherGames\NGEssentials\entity\pets\hovering\WitherSkullPet;
use NetherGames\NGEssentials\entity\pets\swimming\ElderGuardianPet;
use NetherGames\NGEssentials\entity\pets\swimming\GuardianPet;
use NetherGames\NGEssentials\entity\pets\swimming\SquidPet;
use NetherGames\NGEssentials\entity\pets\walking\CaveSpiderPet;
use NetherGames\NGEssentials\entity\pets\walking\ChickenPet;
use NetherGames\NGEssentials\entity\pets\walking\CompanionPet;
use NetherGames\NGEssentials\entity\pets\walking\CowPet;
use NetherGames\NGEssentials\entity\pets\walking\CreeperPet;
use NetherGames\NGEssentials\entity\pets\walking\DonkeyPet;
use NetherGames\NGEssentials\entity\pets\walking\ElephantPet;
use NetherGames\NGEssentials\entity\pets\walking\EndermanPet;
use NetherGames\NGEssentials\entity\pets\walking\EndermitePet;
use NetherGames\NGEssentials\entity\pets\walking\EvokerPet;
use NetherGames\NGEssentials\entity\pets\walking\HorsePet;
use NetherGames\NGEssentials\entity\pets\walking\HuskPet;
use NetherGames\NGEssentials\entity\pets\walking\IronGolemPet;
use NetherGames\NGEssentials\entity\pets\walking\LlamaPet;
use NetherGames\NGEssentials\entity\pets\walking\MazePet;
use NetherGames\NGEssentials\entity\pets\walking\MooshroomPet;
use NetherGames\NGEssentials\entity\pets\walking\MulePet;
use NetherGames\NGEssentials\entity\pets\walking\OcelotPet;
use NetherGames\NGEssentials\entity\pets\walking\PigPet;
use NetherGames\NGEssentials\entity\pets\walking\PolarBearPet;
use NetherGames\NGEssentials\entity\pets\walking\SheepPet;
use NetherGames\NGEssentials\entity\pets\walking\SilverFishPet;
use NetherGames\NGEssentials\entity\pets\walking\SkeletonHorsePet;
use NetherGames\NGEssentials\entity\pets\walking\SkeletonPet;
use NetherGames\NGEssentials\entity\pets\walking\SnowGolemPet;
use NetherGames\NGEssentials\entity\pets\walking\SpiderPet;
use NetherGames\NGEssentials\entity\pets\walking\StrayPet;
use NetherGames\NGEssentials\entity\pets\walking\VillagerPet;
use NetherGames\NGEssentials\entity\pets\walking\VindicatorPet;
use NetherGames\NGEssentials\entity\pets\walking\WitchPet;
use NetherGames\NGEssentials\entity\pets\walking\WitherSkeletonPet;
use NetherGames\NGEssentials\entity\pets\walking\WolfPet;
use NetherGames\NGEssentials\entity\pets\walking\ZombieHorsePet;
use NetherGames\NGEssentials\entity\pets\walking\ZombiePet;
use NetherGames\NGEssentials\entity\pets\walking\ZombiePigmanPet;
use NetherGames\NGEssentials\entity\pets\walking\ZombieVillagerPet;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class PetFactory
{
    use SingletonTrait;

    /** @var array<string, PetCreator> */
    private array $pets = [];

    public function __construct()
    {
        $this->register(ArrowPet::class, Permissions::STAFF_RANKS);
        $this->register(BatPet::class, [Permissions::RANK_LEGEND]);
        $this->register(BeePet::class, [Permissions::RANK_LEGEND]);
        $this->register(BlazePet::class, [Permissions::RANK_LEGEND]);
        $this->register(CaveSpiderPet::class, [Permissions::RANK_LEGEND]);
        $this->register(ChickenPet::class, [Permissions::RANK_EMERALD]);
        $this->register(CompanionPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_OPAL]);
        $this->register(CowPet::class, [Permissions::RANK_LEGEND]);
        $this->register(CreeperPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_AMETHYST]);
        $this->register(DonkeyPet::class, [Permissions::RANK_LEGEND]);
        $this->register(ElderGuardianPet::class, [Permissions::RANK_LEGEND]);
        $this->register(ElephantPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_SAPPHIRE]);
        $this->register(EnderCrystalPet::class, Permissions::STAFF_RANKS);
        $this->register(EnderDragonPet::class, Permissions::STAFF_RANKS);
        $this->register(EndermanPet::class, [Permissions::RANK_LEGEND]);
        $this->register(EndermitePet::class, [Permissions::RANK_LEGEND]);
        $this->register(EvokerPet::class, [Permissions::RANK_LEGEND]);
        $this->register(GhastPet::class, [Permissions::RANK_LEGEND]);
        $this->register(GuardianPet::class, [Permissions::RANK_LEGEND]);
        $this->register(HorsePet::class, [Permissions::RANK_LEGEND, Permissions::TIER_SAPPHIRE]);
        $this->register(HuskPet::class, [Permissions::RANK_LEGEND]);
        $this->register(IronGolemPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_SAPPHIRE]);
        $this->register(LlamaPet::class, [Permissions::RANK_LEGEND]);
        $this->register(MagmaCubePet::class, [Permissions::RANK_LEGEND]);
        $this->register(MazePet::class, [Permissions::RANK_LEGEND]); //todo: convert to cosmetic
        $this->register(MooshroomPet::class, [Permissions::RANK_LEGEND]);
        $this->register(MulePet::class, [Permissions::RANK_LEGEND]);
        $this->register(OcelotPet::class, [Permissions::RANK_EMERALD]);
        $this->register(PigPet::class, [Permissions::RANK_EMERALD]);
        $this->register(PolarBearPet::class, [Permissions::RANK_LEGEND]);
        $this->register(RabbitPet::class, [Permissions::RANK_LEGEND]);
        $this->register(SheepPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_SAPPHIRE]);
        $this->register(SilverFishPet::class, [Permissions::RANK_LEGEND]);
        $this->register(SkeletonHorsePet::class, [Permissions::RANK_LEGEND]);
        $this->register(SkeletonPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_AMETHYST]);
        $this->register(SlimePet::class, [Permissions::RANK_LEGEND]);
        $this->register(SnowGolemPet::class, [Permissions::RANK_LEGEND, Permissions::TIER_SAPPHIRE]);
        $this->register(SpiderPet::class, [Permissions::RANK_LEGEND]);
        $this->register(SquidPet::class, [Permissions::RANK_LEGEND]);
        $this->register(StrayPet::class, [Permissions::RANK_LEGEND]);
        $this->register(VexPet::class, [Permissions::RANK_LEGEND]);
        $this->register(VillagerPet::class, [Permissions::RANK_LEGEND]);
        $this->register(VindicatorPet::class, [Permissions::RANK_LEGEND]);
        $this->register(WitchPet::class, [Permissions::RANK_LEGEND]);
        $this->register(WitherPet::class, [Permissions::RANK_LEGEND]);
        $this->register(WitherSkeletonPet::class, [Permissions::RANK_LEGEND]);
        $this->register(WitherSkullPet::class, [Permissions::RANK_LEGEND]);
        $this->register(WolfPet::class, [Permissions::RANK_EMERALD]);
        $this->register(ZombieHorsePet::class, [Permissions::RANK_LEGEND]);
        $this->register(ZombiePet::class, [Permissions::RANK_LEGEND, Permissions::TIER_AMETHYST]);
        $this->register(ZombiePigmanPet::class, [Permissions::RANK_LEGEND]);
        $this->register(ZombieVillagerPet::class, [Permissions::RANK_LEGEND]);
    }

    /**
     * @param class-string<Entity> $petClass
     * @param string[] $permissions
     */
    private function register(string $petClass, array $permissions = []): void
    {
        $creator = new PetCreator($petClass, $permissions);
        $this->pets[$creator->getSaveId()] = $creator;
    }

    public function get(string $saveId): ?PetCreator
    {
        return $this->pets[$saveId] ?? null;
    }

    /**
     * @return PetCreator[]
     */
    public function getAll(?Player $player = null): array
    {
        return $player === null ? $this->pets : array_filter($this->pets, function (PetCreator $pet) use ($player): bool {
            return $pet->hasPermission($player);
        });
    }
}