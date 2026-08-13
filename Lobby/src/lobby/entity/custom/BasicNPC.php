<?php
declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\entity\minecraft\NPC;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class BasicNPC extends NPC
{

    public function __construct(
        private string $content,
        string         $title,
        Location       $location,
        Skin           $skin,
        array          $buttons = [],
        ?CompoundTag   $nbt = null,
        ?string        $openingSound = "beacon.power",
        ?int           $openingPitch = 1
    )
    {
        parent::__construct(title: $title, location: $location, skin: $skin, buttons: $buttons, nbt: $nbt, openingSound: $openingSound, openingPitch: $openingPitch);
    }

    public static function getNetworkTypeId(): string
    {
        return "minecraft:npc";
    }

    public function getPickerOffset(): int
    {
        return -50;
    }

    public function resolveContent(Player $player): array
    {
        return [$this->content, $this->getButtons()];
    }
}