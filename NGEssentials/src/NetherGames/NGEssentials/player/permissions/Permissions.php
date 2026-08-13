<?php
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\permissions;

use pocketmine\player\Player;
use function count;

final class Permissions
{

    public const GAME_BW_ADMIN = 'nethergames.bw.admin';
    public const GAME_CQ_ADMIN = 'nethergames.cq.admin';
    public const GAME_DUELS_ADMIN = 'nethergames.duels.admin';
    public const GAME_MM_ADMIN = 'nethergames.mm.admin';
    public const GAME_SW_ADMIN = 'nethergames.sw.admin';
    public const GAME_TB_ADMIN = 'nethergames.bridge.admin';

    public const PERK_NICK = 'nethergames.perk.nick';
    public const PERK_NICK_CUSTOM = self::PERK_NICK . '.custom';
    public const PERK_NICK_RANDOM = self::PERK_NICK . '.random';

    public const BYPASS_CHAT_FILTER = 'nethergames.bypass.chatfilter';

    public const PLOT_ADMIN = 'myplot.admin';
    public const PLOT_ADMIN_RESET = 'myplot.admin.reset';
    public const PLOT_ADMIN_ADDHELPER = 'myplot.admin.addhelper';
    public const PLOT_ADMIN_REMOVEHELPER = 'myplot.admin.removehelper';
    public const PLOT_ADMIN_BUILD_PLOT = 'myplot.admin.build.plot';
    public const PLOT_ADMIN_BUILD_READ = 'myplot.admin.build.road';

    public const PLOT_CREATIVE_BASE = 'myplot.claimplots.Creative';
    public const PLOT_CREATIVE_UNLIMITED = self::PLOT_CREATIVE_BASE . '.unlimited';
    public const PLOT_CREATIVE_2 = self::PLOT_CREATIVE_BASE . '.2';

    public const PLOT_MEGA_BASE = 'myplot.claimplots.MEGA';
    public const PLOT_MEGA_UNLIMITED = self::PLOT_MEGA_BASE . '.unlimited';
    public const PLOT_MEGA_2 = self::PLOT_MEGA_BASE . '.2';
    public const PLOT_MEGA_5 = self::PLOT_MEGA_BASE . '.5';
    public const PLOT_MEGA_10 = self::PLOT_MEGA_BASE . '.10';

    public const PLOT_PLATINUM_BASE = 'myplot.claimplots.Platinum';
    public const PLOT_PLATINUM_UNLIMITED = self::PLOT_PLATINUM_BASE . '.unlimited';
    public const PLOT_PLATINUM_4 = self::PLOT_PLATINUM_BASE . '.4';
    public const PLOT_PLATINUM_8 = self::PLOT_PLATINUM_BASE . '.8';
    public const PLOT_PLATINUM_16 = self::PLOT_PLATINUM_BASE . '.16';

    public const RANK_ADMIN = 'nethergames.admin';
    public const RANK_GAME_DESIGNER = "nethergames.game_designer";
    public const RANK_SUPERVISOR = 'nethergames.supervisor';
    public const RANK_MOD = 'nethergames.mod';
    public const RANK_ADVISOR = 'nethergames.advisor';
    public const RANK_ARTIST = 'nethergames.artist';
    public const RANK_TRAINEE_BUILDER = 'nethergames.trainee_builder';
    public const RANK_BUILDER = 'nethergames.builder';
    public const RANK_CREW = 'nethergames.staff';
    public const RANK_MEDIA = 'nethergames.media';
    public const RANK_DEVELOPER = 'nethergames.developer';
    public const RANK_DIRECTOR = 'nethergames.director';
    public const RANK_EMERALD = 'nethergames.vip.emerald';
    public const RANK_LEGEND = 'nethergames.vip.legend';
    public const RANK_OWNER = 'nethergames.executive';
    public const RANK_PARTNER = 'nethergames.partner';
    public const RANK_TITAN = 'nethergames.vip.titan';
    public const RANK_TESTER = 'nethergames.tester';
    public const RANK_TRAINEE = 'nethergames.trainee';
    public const RANK_DISCORD = 'nethergames.discord';
    public const RANK_ULTRA = 'nethergames.vip.ultra';
    public const RANK_VOTER = 'nethergames.voter';
    public const RANK_YOUTUBE = 'nethergames.youtube';

    public const TIER_AMETHYST = 'nethergames.tier.amethyst';
    public const TIER_BRONZE = 'nethergames.tier.bronze';
    public const TIER_PLATINUM = 'nethergames.tier.platinum';
    public const TIER_DIAMOND = 'nethergames.tier.diamond';
    public const TIER_GOLD = 'nethergames.tier.gold';
    public const TIER_OPAL = 'nethergames.tier.opal';
    public const TIER_SAPPHIRE = 'nethergames.tier.sapphire';
    public const TIER_SILVER = 'nethergames.tier.silver';
    public const TIER_STEEL = 'nethergames.tier.steel';

    public const DEFAULT_COMMAND_PERMISSION = 'nethergames.player';

    public const STAFF_RANKS = [
        self::RANK_ARTIST,
        self::RANK_BUILDER,
        self::RANK_DEVELOPER,
        self::RANK_GAME_DESIGNER,
        self::RANK_TRAINEE,
        self::RANK_DISCORD,
        self::RANK_MEDIA
    ];

    public static function isStaff(Player $player): bool
    {
        foreach (self::STAFF_RANKS as $permission) {
            if ($player->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function hasPermission(Player $player, array $permissions = []): bool
    {
        foreach ($permissions as $permission) {
            if ($player->hasPermission($permission)) {
                return true;
            }
        }

        return count($permissions) === 0;
    }
}