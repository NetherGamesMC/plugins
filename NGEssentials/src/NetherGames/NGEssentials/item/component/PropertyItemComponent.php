<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\item\component;

abstract class PropertyItemComponent extends ItemComponent
{
    public function isProperty(): bool
    {
        return true;
    }
}