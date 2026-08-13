<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions\ranks;


use pocketmine\permission\PermissionAttachment;
use pocketmine\utils\TextFormat;
use function strtolower;

abstract class Rank
{
    /**
     * @return string[] An array of the tags added to the attachment
     */
    public function setPermissions(PermissionAttachment $attachment): array
    {
        return [];
    }

    public function getColor(): string
    {
        return TextFormat::YELLOW;
    }

    public function getName(): string
    {
        return strtolower(TextFormat::clean($this->getTag()));
    }

    public function getTag(): string
    {
        return '';
    }
}