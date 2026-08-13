<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat\types;


use function current;
use function next;
use function reset;
use function str_split;

class RandomChatColor extends ChatColor
{

    public function __construct(
        string        $displayName,
        /** @var string[] */
        private array $colors,
        array         $permissions = []
    )
    {
        parent::__construct($displayName, '', $permissions);
    }

    public function formatText(string $message): string
    {
        $string = '';
        foreach (str_split($message) as $letter) {
            if ($letter === ' ') {
                $string .= $letter;
            } else {
                $string .= current($this->colors) . $letter;

                if (next($this->colors) === false) {
                    reset($this->colors);
                }
            }
        }

        return $string;
    }
}