<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\chat\types;

class ChatColor
{

    public function __construct(
        private string $displayName,
        private string $textFormat = '',
        /** @var string[] */
        private array  $permissions = []
    )
    {
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function formatText(string $message): string
    {
        return $this->getTextFormat() . $message;
    }

    public function getTextFormat(): string
    {
        return $this->textFormat;
    }
}