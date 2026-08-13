<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\permutations;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

final class Permutation
{

    private CompoundTag $components;

    public function __construct(private readonly string $condition)
    {
        $this->components = CompoundTag::create();
    }

    /**
     * Returns the permutation with the provided component added to the current list of components.
     */
    public function withComponent(string $component, Tag $value): self
    {
        $this->components->setTag($component, $value);
        return $this;
    }

    /**
     * Returns the permutation in the correct NBT format supported by the client.
     */
    public function toNBT(): CompoundTag
    {
        return CompoundTag::create()
            ->setString("condition", $this->condition)
            ->setTag("components", $this->components);
    }
}