<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\block\permutations;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final readonly class BlockProperty
{

    /**
     * @param list<Tag> $values
     */
    public function __construct(private string $name, private array $values)
    {
    }

    /**
     * Returns the name of the block property provided in the constructor.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the array of possible values of the block property provided in the constructor.
     * @return list<Tag>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Returns the block property in the correct NBT format supported by the client.
     */
    public function toNBT(): CompoundTag
    {
        return CompoundTag::create()
            ->setString("name", $this->name)
            ->setTag("enum", new ListTag($this->values));
    }
}