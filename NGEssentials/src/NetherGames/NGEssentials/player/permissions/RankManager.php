<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\permissions;


use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\ranks\EmeraldRank;
use NetherGames\NGEssentials\player\permissions\ranks\LegendRank;
use NetherGames\NGEssentials\player\permissions\ranks\PartnerRank;
use NetherGames\NGEssentials\player\permissions\ranks\Rank;
use NetherGames\NGEssentials\player\permissions\ranks\TitanRank;
use NetherGames\NGEssentials\player\permissions\ranks\UltraRank;
use NetherGames\NGEssentials\player\permissions\ranks\VoterRank;
use NetherGames\NGEssentials\player\permissions\ranks\YouTubeRank;
use NetherGames\NGEssentials\player\permissions\staff\AdminRank;
use NetherGames\NGEssentials\player\permissions\staff\AdvisorRank;
use NetherGames\NGEssentials\player\permissions\staff\BuilderRank;
use NetherGames\NGEssentials\player\permissions\staff\CrewRank;
use NetherGames\NGEssentials\player\permissions\staff\DesignerRank;
use NetherGames\NGEssentials\player\permissions\staff\DevRank;
use NetherGames\NGEssentials\player\permissions\staff\DiscordRank;
use NetherGames\NGEssentials\player\permissions\staff\GameDesignerRank;
use NetherGames\NGEssentials\player\permissions\staff\HonouredRank;
use NetherGames\NGEssentials\player\permissions\staff\MediaRank;
use NetherGames\NGEssentials\player\permissions\staff\ModRank;
use NetherGames\NGEssentials\player\permissions\staff\StaffRank;
use NetherGames\NGEssentials\player\permissions\staff\SupervisorRank;
use NetherGames\NGEssentials\player\permissions\staff\SupportRank;
use NetherGames\NGEssentials\player\permissions\staff\TesterRank;
use NetherGames\NGEssentials\player\permissions\staff\TraineeBuilderRank;
use NetherGames\NGEssentials\player\permissions\staff\TraineeRank;
use NetherGames\NGEssentials\player\permissions\tiers\AmethystTier;
use NetherGames\NGEssentials\player\permissions\tiers\BronzeTier;
use NetherGames\NGEssentials\player\permissions\tiers\DiamondTier;
use NetherGames\NGEssentials\player\permissions\tiers\GoldTier;
use NetherGames\NGEssentials\player\permissions\tiers\OpalTier;
use NetherGames\NGEssentials\player\permissions\tiers\PlatinumTier;
use NetherGames\NGEssentials\player\permissions\tiers\SapphireTier;
use NetherGames\NGEssentials\player\permissions\tiers\SilverTier;
use NetherGames\NGEssentials\player\permissions\tiers\SteelTier;
use NetherGames\NGEssentials\player\permissions\tiers\Tier;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function strtolower;
use function time;

class RankManager
{
    public const NO_RANK = 'No Rank';
    /** @var Rank[] */
    private array $ranks = [];
    /** @var StaffRank[] */
    private array $staffRanks = [];
    /** @var Tier[] */
    private array $tiers = [];
    /** @var string[] */
    private array $tagToName = [];

    public function __construct(private PlayerManager $manager)
    {
        foreach ([
                     new AdminRank(),
                     new AdvisorRank(),
                     new DevRank(),
                     new SupervisorRank(),
                     new BuilderRank(),
                     new DiscordRank(),
                     new SupportRank(),
                     new ModRank(),
                     new CrewRank(),
                     new TraineeRank(),
                     new TraineeBuilderRank(),
                     new DesignerRank(),
                     new GameDesignerRank(),
                     new MediaRank(),
                     new TesterRank(),
                     new HonouredRank(),

                     new PartnerRank(),
                     new YouTubeRank(),
                     new TitanRank(),
                     new LegendRank(),
                     new EmeraldRank(),
                     new UltraRank(),
                     new VoterRank()
                 ] as $rank) {
            $this->registerRank($rank);
        }

        foreach ([
                     new PlatinumTier(),
                     new DiamondTier(),
                     new SapphireTier(),
                     new AmethystTier(),
                     new OpalTier(),
                     new GoldTier(),
                     new SilverTier(),
                     new BronzeTier(),
                     new SteelTier(),
                 ] as $tier) {
            $this->registerTier($tier);
        }
    }

    public function registerRank(Rank|StaffRank $rank): void
    {
        $this->ranks[$name = $rank->getName()] = $rank;
        $this->tagToName[$rank->getTag()] = $name;
    }

    public function registerTier(Tier $tier): void
    {
        $this->tiers[$tier->getCredits()] = $tier;
    }

    public function updatePermissions(NGPlayer $player): void
    {
        $playerData = $this->getManager()->getPlugin()->getPlayerData();

        $attachment = $player->getPermissionAttachment();
        $attachment->clearPermissions();

        /** @var array<string, bool> $permissions */
        /** @var string[] $tags */
        [$permissions, $tags] = $this->getPermissions(
            $playerData->getArray($player, PlayerData::PERMISSIONS),
            $playerData->getArray($player, PlayerData::RANK),
            $playerData->getInt($player, PlayerData::STATUS_CREDITS),
            $playerData->getInt($player, PlayerData::TITAN_EXPIRE),
            $playerData->getInt($player, PlayerData::VOTE_TIME)
        );

        if ($permissions[Permissions::RANK_ADMIN] ?? false) {
            $player->setBasePermission(DefaultPermissions::ROOT_OPERATOR, true);
        }

        $player->setRankTags($tags);
        $attachment->setPermissions($permissions);
    }

    /**
     * Returns an array containing the permissions in the first index and the tags in the second index
     *
     * @param array<string, bool> $permissions
     * @param string[] $rankNames
     * @return array{0: array<string, bool>, 1: string[]}
     */
    public function getPermissions(array $permissions, array $rankNames, int $credits, int $titanExpire, int $voteTime): array
    {
        if ($permissions[Permissions::PERK_NICK] ?? false) {
            $permissions[Permissions::PERK_NICK_CUSTOM] = true;
            $permissions[Permissions::PERK_NICK_RANDOM] = true;

            unset($permissions[Permissions::PERK_NICK]);
        }

        $attachment = new PermissionAttachment($this->getManager()->getPlugin());
        $attachment->setPermissions($permissions);

        $tags = [];

        foreach ($this->getRanks($this->calculateRankNames($rankNames, $titanExpire, $voteTime)) as $rank) {
            $tags = [...$tags, ...$rank->setPermissions($attachment)];
        }

        if (($tier = $this->getTierFromCredits($credits)) !== null) {
            $tier->setPermissions($attachment);
        }

        $permissions = $attachment->getPermissions();
        if (!($permissions[Permissions::PLOT_CREATIVE_UNLIMITED] ?? false)) {
            $permissions[Permissions::PLOT_CREATIVE_2] = true;
        }

        return [$permissions, array_values(array_unique($tags))];
    }

    /**
     * @return PlayerManager
     */
    public function getManager(): PlayerManager
    {
        return $this->manager;
    }

    /**
     * @param string[] $ranks
     * @return string[]
     */
    private function calculateRankNames(array $ranks, int $titanExpire, int $voteTime): array
    {
        $ranks = array_map('strtolower', $ranks);

        if ($titanExpire > time()) {
            $ranks[] = (new TitanRank())->getName();
        }

        if (count($ranks) === 0 && (time() - $voteTime) < (60 * 60 * 24)) {
            $ranks[] = (new VoterRank())->getName();
        }

        return $ranks;
    }

    /**
     * @param string[] $rankNames
     * @return Rank[]
     */
    private function getRanks(array $rankNames): array
    {
        $playerRanks = [];

        foreach ($this->ranks as $rankName => $rank) {
            if (in_array($rankName, $rankNames, true)) {
                $playerRanks[$rankName] = $rank;
            }
        }

        if (count($playerRanks) !== count($rankNames)) {
            $this->getManager()->getPlugin()->getLogger()->error("Failed to get all ranks for " . implode(", ", $rankNames) . " got " . implode(", ", array_keys($playerRanks)));
        }

        return $playerRanks;
    }

    public function getTier(Player $player): ?Tier
    {
        $playerData = $this->getManager()->getPlugin()->getPlayerData();
        return $this->getTierFromCredits($playerData->getInt($player, PlayerData::STATUS_CREDITS));
    }

    public function getTierFromCredits(int $credits): ?Tier
    {
        foreach ($this->tiers as $tierCredits => $tier) {
            if ($credits >= $tierCredits) {
                return $tier;
            }
        }

        return null;
    }

    public function getNextTier(?Tier $current): ?Tier
    {
        $isNext = $current === null;
        foreach ($this->tiers as $tier) {
            if ($tier === $current) {
                $isNext = true;
            }

            if ($isNext) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return Rank|StaffRank|null
     */
    public function getRankByName(string $name): StaffRank|Rank|null
    {
        return $this->staffRanks[$name] ?? $this->ranks[$name] ?? null;
    }

    public function updateNameTag(NGPlayer $player): void
    {
        $playerManager = $this->getManager();
        $playerData = $playerManager->getPlugin()->getPlayerData();
        $selectedRank = $playerData->getString($player, PlayerData::SELECTED_RANK);
        $rankTags = $player->getRankTags();

        $rankTag = '';
        $tierTag = '';
        $color = TextFormat::YELLOW;

        if ($selectedRank === self::NO_RANK) {
            if (count($rankTags) === 0) {
                $playerData->setValue($player, PlayerData::SELECTED_RANK, $selectedRank = '');
            }
        } elseif ($selectedRank !== '') {
            $selectedRank = strtolower($selectedRank);

            $rank = $this->getRankByName($selectedRank);

            if ($rank !== null && in_array($tag = $rank->getTag(), $rankTags, true)) {
                $rankTag = $tag;
                $color = $rank->getColor();
            } else {
                $playerData->setValue($player, PlayerData::SELECTED_RANK, $selectedRank = '');
            }
        }

        if ($selectedRank === '' && isset($rankTags[0])) {
            $selectedRank = $this->getNameByTag($rankTags[0]);

            if ($selectedRank !== null) {
                $rank = $this->getRankByName($selectedRank);

                if ($rank !== null) {
                    $rankTag = $rank->getTag();
                    $color = $rank->getColor();
                }
            }
        }

        if (($tier = $this->getTier($player)) !== null) {
            $tierTag = $tier->getTag();
        }

        if ($rankTag !== '') {
            $rankTag .= ' ';
        }

        $playerData->setValue($player, PlayerData::RANKTAG, $tierTag . $rankTag . TextFormat::RESET . $color . $player->getName());

        $player->setNameTag($playerManager->getNameTag($player, TextFormat::YELLOW));
        $player->setDisplayName($playerManager->getPlayerColouredName($player));
    }

    /**
     * @return Tier[]
     */
    public function getTiers(): array
    {
        return array_values($this->tiers);
    }

    public function getNameByTag(string $tag): ?string
    {
        return $this->tagToName[$tag] ?? null;
    }
}