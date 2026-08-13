<?php

namespace NetherGames\NGEssentials\elasticsearch\entry;

abstract class Entry
{
    abstract public function asArray(): array;
}