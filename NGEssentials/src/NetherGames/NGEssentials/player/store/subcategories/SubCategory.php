<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\store\subcategories;


use NetherGames\NGEssentials\player\store\categories\Category;
use pocketmine\player\Player;

abstract class SubCategory
{
    public const NAME = 0;
    public const PRICE = 1;
    public const DESCRIPTION = 2;

    public function __construct(private Category $category, private string $name, private int $id, private array $elements)
    {
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    abstract public function sendForm(Player $player, ?callable $onBack = null): void;
}