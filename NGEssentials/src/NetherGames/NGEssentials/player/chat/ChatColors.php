<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat;


use NetherGames\NGEssentials\player\chat\types\ChatColor;
use NetherGames\NGEssentials\player\chat\types\RandomChatColor;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use function array_filter;

class ChatColors
{
    use SingletonTrait;

    /** @var ChatColor[] */
    private array $colors;

    public function __construct()
    {
        $this->register(0, new ChatColor('Reset'));

        $this->register(1, new ChatColor('Green', TextFormat::GREEN, [Permissions::TIER_STEEL, Permissions::RANK_ULTRA]));
        $this->register(2, new ChatColor('Aqua', TextFormat::AQUA, [Permissions::TIER_STEEL, Permissions::RANK_ULTRA]));
        $this->register(3, new ChatColor('Red', TextFormat::RED, [Permissions::TIER_STEEL, Permissions::RANK_ULTRA]));

        $this->register(4, new ChatColor('Yellow', TextFormat::YELLOW, [Permissions::TIER_BRONZE, Permissions::RANK_ULTRA]));
        $this->register(5, new ChatColor('White', TextFormat::WHITE, [Permissions::TIER_SILVER, Permissions::RANK_ULTRA]));

        $this->register(6, new ChatColor('Gold', TextFormat::GOLD, [Permissions::TIER_GOLD, Permissions::RANK_EMERALD]));
        $this->register(7, new ChatColor('Light Purple', TextFormat::LIGHT_PURPLE, [Permissions::TIER_OPAL, Permissions::RANK_EMERALD]));

        $this->register(8, new ChatColor('Black', TextFormat::BLACK, [Permissions::TIER_AMETHYST, Permissions::RANK_LEGEND]));
        $this->register(9, new ChatColor('Dark Blue', TextFormat::DARK_BLUE, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(10, new ChatColor('Dark Green', TextFormat::DARK_GREEN, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(11, new ChatColor('Dark Aqua', TextFormat::DARK_AQUA, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(12, new ChatColor('Dark Red', TextFormat::DARK_RED, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(13, new ChatColor('Dark Purple', TextFormat::DARK_PURPLE, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(14, new ChatColor('Gray', TextFormat::GRAY, [Permissions::TIER_AMETHYST, Permissions::RANK_LEGEND]));
        $this->register(15, new ChatColor('Dark Gray', TextFormat::DARK_GRAY, [Permissions::TIER_SAPPHIRE, Permissions::RANK_LEGEND]));
        $this->register(16, new ChatColor('Blue', TextFormat::BLUE, [Permissions::TIER_AMETHYST, Permissions::RANK_LEGEND]));

        $this->register(20, new RandomChatColor('Rainbow', [TextFormat::RED, TextFormat::GOLD, TextFormat::YELLOW, TextFormat::GREEN, TextFormat::BLUE, TextFormat::LIGHT_PURPLE, TextFormat::DARK_PURPLE], [Permissions::RANK_TITAN]));
    }

    public function register(int $saveId, ChatColor $color): void
    {
        $this->colors[$saveId] = $color;
    }

    public function getColor(Player $player, int $saveId): ChatColor
    {
        $color = $this->colors[$saveId] ?? $this->colors[0];

        if ($this->hasPermission($player, $color)) {
            return $color;
        }

        return $this->colors[0];
    }

    public function hasPermission(Player $player, ChatColor $color): bool
    {
        return Permissions::hasPermission($player, $color->getPermissions());
    }

    /**
     * @param Player $player
     * @return ChatColor[]
     */
    public function getColorsByPlayer(Player $player): array
    {
        return array_filter($this->getColors(), function (ChatColor $color) use ($player): bool {
            return $this->hasPermission($player, $color);
        });
    }

    /**
     * @param bool $special
     * @return ChatColor[]
     */
    public function getColors(bool $special = true): array
    {
        if ($special) {
            return $this->colors;
        }

        return array_filter($this->getColors(), static function (ChatColor $color): bool {
            return !$color instanceof RandomChatColor;
        });
    }
}