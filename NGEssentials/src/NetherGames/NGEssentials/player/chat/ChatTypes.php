<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\chat;


use InvalidArgumentException;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\chat\types\AdminChat;
use NetherGames\NGEssentials\player\chat\types\ChatType;
use NetherGames\NGEssentials\player\chat\types\GlobalChat;
use NetherGames\NGEssentials\player\chat\types\GuildChat;
use NetherGames\NGEssentials\player\chat\types\ModerationChat;
use NetherGames\NGEssentials\player\chat\types\PartyChat;
use NetherGames\NGEssentials\player\chat\types\RankedChat;
use NetherGames\NGEssentials\player\chat\types\StaffChat;
use NetherGames\NGEssentials\player\chat\types\TraineeChat;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function array_filter;
use function strtolower;
use function trim;

class ChatTypes
{
    use SingletonTrait;

    public const GLOBAL_CHAT = 0;
    public const PARTY_CHAT = 1;
    public const GUILD_CHAT = 2;
    public const TRAINEE_CHAT = 3;
    public const STAFF_CHAT = 4;
    public const MODERATION_CHAT = 5;
    public const RANKED_CHAT = 6;
    public const ADMIN_CHAT = 7;

    /** @var ChatType[] */
    private array $chatTypes;
    /** @var array<string, int> */
    private array $aliases;

    public function __construct()
    {
        $ess = NGEssentials::getInstance();
        $playerManager = $ess->getPlayerManager();
        $socialManager = $playerManager->getSocialManager();
        $chatManager = $ess->getPlayerManager()->getChatManager();
        $serverManager = $ess->getServerManager();

        $this->register(self::GLOBAL_CHAT, new GlobalChat($chatManager), ['off', 'global']);
        $this->register(self::PARTY_CHAT, new PartyChat($socialManager->getPartyManager()), ['p', 'party']);
        $this->register(self::GUILD_CHAT, new GuildChat($chatManager), ['g', 'guild']);
        $this->register(self::TRAINEE_CHAT, new TraineeChat($chatManager), ['t', 'trainee']);
        $this->register(self::STAFF_CHAT, new StaffChat($chatManager), ['s', 'staff']);
        $this->register(self::MODERATION_CHAT, new ModerationChat($chatManager), ['m', 'mod', 'moderation']);
        $this->register(self::RANKED_CHAT, new RankedChat($chatManager), ['r', 'ranked']);
        $this->register(self::ADMIN_CHAT, new AdminChat($chatManager), $serverManager->getServerType() === ServerManager::FACTIONS ? ['admin'] : ['a', 'admin']);
    }

    /**
     * @param int $saveId
     * @param ChatType $chatType
     * @param string[] $aliases
     */
    public function register(int $saveId, ChatType $chatType, array $aliases = []): void
    {
        $this->chatTypes[$saveId] = $chatType;

        foreach ($aliases as $alias) {
            if (isset($this->aliases[$alias = strtolower(trim($alias))])) {
                throw new InvalidArgumentException("Alias $alias is already registered");
            }

            $this->aliases[$alias] = $saveId;
        }
    }

    public function getChatType(int $saveId): ChatType
    {
        return $this->chatTypes[$saveId] ?? $this->chatTypes[0];
    }

    public function getSaveIdByAlias(string $alias): ?int
    {
        return $this->aliases[strtolower(trim($alias))] ?? null;
    }

    /**
     * @param Player $player
     * @return ChatType[]
     */
    public function getChatTypesByPlayer(Player $player): array
    {
        return array_filter($this->getChatTypes(), static function (ChatType $chatType) use ($player): bool {
            return $chatType->canBeUsed($player);
        });
    }

    /**
     * @return ChatType[]
     */
    public function getChatTypes(): array
    {
        return $this->chatTypes;
    }
}