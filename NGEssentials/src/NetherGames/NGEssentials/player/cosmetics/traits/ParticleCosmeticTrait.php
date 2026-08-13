<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
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

namespace NetherGames\NGEssentials\player\cosmetics\traits;

use Closure;
use InvalidArgumentException;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use NetherGames\NGEssentials\utils\ParticleOptimizer;
use pocketmine\color\Color;
use pocketmine\command\defaults\ParticleCommand;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\particle\Particle;
use function mt_rand;

/**
 * @mixin Cosmetic
 */
trait ParticleCosmeticTrait
{
    /** @var array<string, Particle> */
    private array $cache = [];

    private const PARTICLE_KEY = 'particle';
    private const PARTICLE_NAME_KEY = 'name';
    private const PARTICLE_DATA_KEY = 'data';

    /** @var ?ParticleOptimizer */
    private ?ParticleOptimizer $optimizer = null;
    private ?Closure $particleResolver = null;

    protected function getOptimizer(): ParticleOptimizer
    {
        return $this->optimizer ??= ParticleOptimizer::getInstance();
    }

    /**
     * @return Closure(string, ?string): Particle
     */
    private function getParticleResolver(): Closure
    {
        return $this->particleResolver ??= function (string $name, ?string $data = null) {
            return (function () use ($name, $data) {
                /** @var ParticleCommand $this */
                return $this->getParticle($name, $data);
            })->call(new ParticleCommand());
        };
    }

    protected function getParticle(CosmeticDataEntry $entry): Particle
    {
        if ($entry->id === 100) {
            return new DustParticle(new Color(mt_rand(1, 300), mt_rand(1, 300), mt_rand(1, 3000)));
        }

        return $this->cache[$entry->getHash()] ??= ($this->getParticleResolver())(
            $entry->data[self::PARTICLE_KEY][self::PARTICLE_NAME_KEY] ?? throw new InvalidArgumentException("Missing particle key for cosmetic $entry->id"),
            $entry->data[self::PARTICLE_KEY][self::PARTICLE_DATA_KEY] ?? null
        );
    }

    protected function isParticleCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::PARTICLE_KEY]);
    }
}