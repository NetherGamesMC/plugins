<?php
declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\entity\minecraft\NPC;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class RandomPhraseNPC extends NPC
{
    public function __construct(private array $phrases, string $title, Location $location, Skin $skin, array $buttons = [], ?CompoundTag $nbt = null, ?string $openingSound = "beacon.power", ?int $openingPitch = 1)
    {
        parent::__construct(title: $title, location: $location, skin: $skin, buttons: $buttons, nbt: $nbt, openingSound: $openingSound, openingPitch: $openingPitch);
    }

    public function resolveContent(Player $player): array
    {
        return [$this->phrases[array_rand($this->phrases)], $this->getButtons()];
    }

    public function getPickerOffset(): int
    {
        return -50;
    }
}