<?php

declare(strict_types=1);


namespace libVanilla\entity;


use libVanilla\entity\traits\BabyTrait;
use pocketmine\entity\Ageable;

abstract class AgeableMonster extends Monster implements Ageable
{
    use BabyTrait;
}