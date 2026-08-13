<?php

declare(strict_types=1);

namespace skywars\drops;

use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use skywars\entities\LuckyBlock;
use skywars\SWArena;

abstract class BaseDrop
{
    public const PRIORITY_ULTRA_LOW = 0;
    public const PRIORITY_LOW = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_HIGH = 3;
    public const PRIORITY_ULTRA_HIGH = 4;

    final public function willDrop(): bool
    {
        return ((mt_rand() / getrandmax()) * 100) <= $this->dropChance();
    }

    abstract public function dropChance(): float|int;

    public function getPriority(): int
    {
        return self::PRIORITY_MEDIUM;
    }

    final public function playSound(Location $to, string $sound): void
    {
        $packet = new PlaySoundPacket;
        $packet->soundName = $sound;
        $packet->x = $to->x;
        $packet->y = $to->y;
        $packet->z = $to->z;
        $packet->volume = 500;
        $packet->pitch = 1;

        $to->getWorld()->broadcastPacketToViewers($to->asVector3(), $packet);
    }

    public function nearMiddle(LuckyBlock $block): bool
    {
        return $block->getPosition()->maxPlainDistance($block->getWorld()->getSpawnLocation()) <= 15;
    }

    abstract public function drop(Player $player, LuckyBlock $block, SWArena $arena): void;
}