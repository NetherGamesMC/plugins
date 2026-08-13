<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\Tag;

final class UseAnimationComponent extends PropertyItemComponent
{

    public const ANIMATION_EAT = 1;
    public const ANIMATION_DRINK = 2;


    public function __construct(private readonly int $animation)
    {
    }

    public function getName(): string
    {
        return "use_animation";
    }

    public function getValue(int $protocolId): Tag
    {
        return new IntTag($this->animation);
    }
}