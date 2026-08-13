<?php


namespace NetherGames\NGEssentials\player;

use JsonException;
use libforms\elements\Dropdown;
use libforms\elements\Label;
use libforms\elements\Slider;
use libforms\elements\Toggle;
use libforms\FormManager;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\utils\StatsBarType;
use NetherGames\NGEssentials\ServerManager;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_values;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function json_decode;
use function strtolower;
use const JSON_THROW_ON_ERROR;

class GameSettings
{
    public const STORAGE_VERSION_KEY = 'version';
    public const STORAGE_VERSION = 3;

    public const LEFT_CLICK_NPC = -1;
    // empty
    public const POPUP_MESSAGES = -3;
    public const REGION_QUEUING = -4;
    public const TOUCH_ONLY = -5;
    public const CHEST_COMBAT_COOLDOWN = -6;
    public const COOLDOWN_SECONDS = -7;
    public const ANNOUNCE_PLAYERS = -8;
    public const STATS_BAR = -9;

    public const BW_QUICK_BUY = 0;
    public const BW_SHOP_SLIDER = 1;
    public const BW_CHEST_UI = 2;

    public const CQ_QUICK_BUY = 0;
    public const CQ_SHOP_SLIDER = 1;
    public const CQ_CHEST_UI = 2;

    public const TB_LAYOUT = 10;

    private const DATA_TYPES = [
        self::GLOBAL => [
            self::LEFT_CLICK_NPC => self::BOOL,
            self::POPUP_MESSAGES => self::BOOL,
            self::ANNOUNCE_PLAYERS => self::BOOL,
            self::STATS_BAR => self::INT,
            self::REGION_QUEUING => self::BOOL,
            self::TOUCH_ONLY => self::BOOL,
            self::CHEST_COMBAT_COOLDOWN => self::BOOL,
            self::COOLDOWN_SECONDS => self::INT,
        ],
        ServerManager::BW => [
            self::BW_QUICK_BUY => self::ARRAY,
            self::BW_SHOP_SLIDER => self::BOOL,
            self::BW_CHEST_UI => self::BOOL,
        ],
        ServerManager::CQ => [
            self::CQ_QUICK_BUY => self::ARRAY,
            self::CQ_SHOP_SLIDER => self::BOOL,
            self::CQ_CHEST_UI => self::BOOL,
        ],
        ServerManager::TB => [
            self::TB_LAYOUT => self::ARRAY,
        ]
    ];

    public const GLOBAL = 'global';

    //DATA TYPES
    public const ARRAY = 0;
    public const BOOL = 1;
    public const INT = 2;

    /** @var PlayerData */
    private PlayerData $playerData;

    public function __construct(PlayerData $playerData)
    {
        $this->playerData = $playerData;
    }

    public function processLoading(string $data): array
    {
        try {
            $gameSettings = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($gameSettings[self::STORAGE_VERSION_KEY])) {
                $gameSettings[self::STORAGE_VERSION_KEY] = 1;
                unset($gameSettings['CTF']);
            }
            if ($gameSettings[self::STORAGE_VERSION_KEY] === 1) {
                $gameSettings[self::STORAGE_VERSION_KEY] = 2;

                $newGameSettings = [];
                foreach ($gameSettings as $key => $value) {
                    $newGameSettings[strtolower($key)] = $value; //we lowercased the serverTypes
                }
                $gameSettings = $newGameSettings;
            }

            if ($gameSettings[self::STORAGE_VERSION_KEY] === 2) {
                $gameSettings[self::STORAGE_VERSION_KEY] = self::STORAGE_VERSION;

                unset($gameSettings[self::GLOBAL][-2]);
            }

            return $gameSettings;
        } catch (JsonException $e) {
            return [];
        }
    }

    public function getPlayerData(): PlayerData
    {
        return $this->playerData;
    }

    /**
     * @param Player $player
     * @param int $id
     * @param string $serverType
     * @return array
     */
    public function getArray(Player $player, int $id, string $serverType = ''): array
    {
        return (array)$this->getValue($player, $id, $serverType);
    }

    /**
     * @param Player $player
     * @param int $id
     * @param string $serverType
     * @return mixed
     */
    private function getValue(Player $player, int $id, string $serverType): mixed
    {
        $playerData = $this->getPlayerData();
        if ($serverType === '') {
            $serverType = $playerData->getPlugin()->getServerManager()->getServerType();
        }

        if (!isset(self::DATA_TYPES[$serverType][$id])) {
            $serverType = self::GLOBAL;
        }

        $gameSettings = $playerData->getArray($player, PlayerData::GAME_SETTINGS);
        return $gameSettings[$serverType][$id] ?? $this->getDefaultValue($player, $serverType, $id);
    }

    /**
     * @param Player $player
     * @param string $serverType
     * @param int $id
     * @return array|bool|int|float|string|null
     */
    public function getDefaultValue(Player $player, string $serverType, int $id): float|array|bool|int|string|null
    {
        /** @var NGPlayer $player */
        switch ($serverType) {
            case self::GLOBAL:
                switch ($id) {
                    case self::COOLDOWN_SECONDS:
                        return 5;
                    case self::CHEST_COMBAT_COOLDOWN:
                    case self::POPUP_MESSAGES:
                        return $player->getInputMode() !== InputMode::MOUSE_KEYBOARD;
                    case self::ANNOUNCE_PLAYERS:
                        return $player->hasPermission(Permissions::RANK_ULTRA);
                    case self::LEFT_CLICK_NPC:
                    case self::REGION_QUEUING:
                        return true;
                    case self::STATS_BAR:
                        return StatsBarType::XP->value;
                }
                break;
            case ServerManager::BW:
                switch ($id) {
                    case self::BW_SHOP_SLIDER:
                        return $player->getInputMode() !== InputMode::MOUSE_KEYBOARD;
                    case self::BW_CHEST_UI:
                        return $player->getInputMode() === InputMode::MOUSE_KEYBOARD;
                }
                break;
            case ServerManager::CQ:
                switch ($id) {
                    case self::CQ_SHOP_SLIDER:
                        return $player->getInputMode() !== InputMode::MOUSE_KEYBOARD;
                    case self::CQ_CHEST_UI:
                        return $player->getInputMode() === InputMode::MOUSE_KEYBOARD;
                }
                break;
        }

        $dataType = self::DATA_TYPES[$serverType][$id] ?? self::ARRAY;
        return match ($dataType) {
            self::ARRAY => [],
            self::BOOL => false,
            self::INT => 0,
        };
    }

    /**
     * @param Player $player
     * @param int $id
     * @param mixed $value
     * @param string $serverType
     */
    public function setValue(Player $player, int $id, mixed $value, string $serverType = ''): void
    {
        $playerData = $this->getPlayerData();
        if ($serverType === '') {
            $serverType = $playerData->getPlugin()->getServerManager()->getServerType();
            if ($serverType === ServerManager::SETUP) {
                return;
            }
        }
        $gameSettings = $playerData->getArray($player, PlayerData::GAME_SETTINGS);

        if (!isset(self::DATA_TYPES[$serverType][$id])) {
            $serverType = self::GLOBAL;
        }

        if (($validatedValue = $this->validateValue($id, $serverType, $value)) !== null) {
            if ($validatedValue === $this->getDefaultValue($player, $serverType, $id)) {
                unset($gameSettings[$serverType][$id]);
            } else {
                $gameSettings[$serverType][$id] = $validatedValue;
            }
        }

        $playerData->setValue($player, PlayerData::GAME_SETTINGS, $gameSettings);
    }

    /**
     * @param int $id
     * @param string $serverType
     * @param float|object|int|bool|array|string|null $value
     * @return bool|int|null|array|string|object|float
     */
    public function validateValue(int $id, string $serverType, float|object|int|bool|array|string|null $value): float|object|int|bool|array|string|null
    {
        $dataType = self::DATA_TYPES[$serverType][$id];

        if ($dataType === self::ARRAY && is_array($value)) {
            return $value;
        }

        if ($dataType === self::BOOL) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_string($value) || is_int($value) || is_float($value)) {
                return (bool)$value;
            }
        }

        if ($dataType == self::INT) {
            return (int)$value;
        }

        return null;
    }

    public function sendForm(Player $player, ?callable $onBack): void
    {
        /** @var NGPlayer $player */
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $form->setTitle('Minigame Settings');
            $form->addElement(new Label(TextFormat::GOLD . 'Global'));

            $form->addElement(new Toggle('Region Queuing', $this->getBool($player, self::REGION_QUEUING), function (Player $player, bool $value) {
                $this->setValue($player, self::REGION_QUEUING, $value);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Enabled region queuing. You will now queue to servers that are in the same region as the proxy you are connected to.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Disabled region queuing. You will now queue to servers on both regions.');
                }
            }));
            if ($player->getInputMode() === InputMode::TOUCHSCREEN) {
                $form->addElement(new Toggle('Touch Only', $this->getBool($player, self::TOUCH_ONLY), function (Player $player, bool $value) {
                    $this->setValue($player, self::TOUCH_ONLY, $value);

                    if ($value) {
                        $player->sendMessage(TextFormat::GREEN . 'Enabled touch only queuing. You will now only queue with other players who use touch controls. This may extend the waiting time for matches.');
                    } else {
                        $player->sendMessage(TextFormat::RED . 'Disabled touch only queuing. You will no longer only queue with other players who use touch controls.');
                    }
                }));
            }
            $form->addElement(new Toggle('Popup Messages', $this->getBool($player, self::POPUP_MESSAGES), function (Player $player, bool $value): void {
                $this->setValue($player, GameSettings::POPUP_MESSAGES, $value);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Enabled popup game messages. Applicable messages will now be shown as popups in-game to prevent chat spam.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Disabled popup game messages. Applicable messages will no longer be shown as popups in-game.');
                }
            }));
            $form->addElement(new Toggle('Left click NPCs', $this->getBool($player, self::LEFT_CLICK_NPC), function (Player $player, bool $value): void {
                $this->setValue($player, self::LEFT_CLICK_NPC, $value);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Enabled left click NPCs in Bedwars. You will now be able to use both left click and right click to activate the shop and upgrader.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Disabled left click NPCs in Bedwars. You will no longer be able to use left click to activate the shop and upgrader.');
                }
            }));
            $form->addElement(new Toggle('Announce Game Players', $this->getBool($player, self::ANNOUNCE_PLAYERS), function (Player $player, bool $value): void {
                $this->setValue($player, self::ANNOUNCE_PLAYERS, $value);
                if ($value) {
                    $player->sendMessage(
                        "§aEnabled Announce Players. You will now be sent a list of the players in your game before every game." .
                        ($player->hasPermission(Permissions::RANK_ULTRA) ? "" : "\n§bYou will need to vote to use this feature. §rYou can vote at §ehttps://ngmc.co/v")
                    );
                } else {
                    $player->sendMessage('§cDisabled Announce Players.');
                }
            }));

            /** @var array<string, int> $options */
            $options = array_combine(
                array_map(fn(StatsBarType $type) => $type->getName(), StatsBarType::cases()),
                array_column(StatsBarType::cases(), 'value')
            );

            $form->addElement(new Dropdown(
                text: 'Stats Bar §g§l[NEW]',
                options: array_keys($options),
                default: array_search($this->getInt($player, self::STATS_BAR), array_values($options), true),
                callable: function (Player $player, int $value) use ($options): void {
                    $selectedOption = array_values($options)[$value];
                    $selectedType = StatsBarType::from($selectedOption);
                    $this->setValue($player, self::STATS_BAR, $selectedOption);

                    $plugin = $this->getPlayerData()->getPlugin();
                    if ($plugin->getServerManager()->getServerType() !== ServerManager::CREATIVE) {
                        $plugin->getPlayerManager()->setStatsBar($player);
                    }

                    if ($selectedType === StatsBarType::OFF) {
                        $player->sendMessage(TextFormat::RED . 'Disabled the stats bar.');
                    } else {
                        $player->sendMessage(TextFormat::GREEN . $selectedType->getName() . ' stats bar enabled.');
                    }
                }
            ));

            $form->addElement(new Label(TextFormat::GOLD . 'Combat Settings'));
            $form->addElement(new Label(TextFormat::YELLOW . "Prevents you from interacting with in-game objects during combat, cooldown timer can be configured and wears off instantly after you have killed a player."));
            $form->addElement(new Toggle("Chest Interaction", $this->getBool($player, self::CHEST_COMBAT_COOLDOWN), function (Player $player, bool $value): void {
                $this->setValue($player, self::CHEST_COMBAT_COOLDOWN, $value);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Cooldown for ' . TextFormat::GOLD . 'Chest Interaction' . TextFormat::GREEN . ' during combat enabled, you may no longer interact with in-game containers within the specified cooldown time.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Cooldown for ' . TextFormat::GOLD . 'Chest Interaction' . TextFormat::RED . ' during combat disabled, you may interact with in-game containers.');
                }
            }));
            $slider = new Slider("Combat Cooldown (seconds)", 2, 10, function (Player $player, int $value): void {
                $this->setValue($player, self::COOLDOWN_SECONDS, $value);

                $player->sendMessage(TextFormat::GREEN . 'Cooldown timer has been set to ' . TextFormat::GOLD . $value . TextFormat::GREEN . ' seconds.');
            });
            $slider->setDefault($this->getInt($player, self::COOLDOWN_SECONDS));
            $form->addElement($slider);

            $form->addElement(new Label(TextFormat::GOLD . 'Bedwars'));
            $form->addElement(new Toggle('Chest UI', $chestUI = $this->getBool($player, self::BW_CHEST_UI, ServerManager::BW), function (Player $player, bool $value): void {
                $this->setValue($player, self::BW_CHEST_UI, $value, ServerManager::BW);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Enabled the Bedwars chest UI. You will now be shown a chest inventory interface when using the shop and upgrader.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Disabled the Bedwars chest UI. You will now be shown a form interface when using the shop and upgrader.');
                }
            }));
            if (!$chestUI) {
                $form->addElement(new Toggle('Shop Slider', $this->getBool($player, self::BW_SHOP_SLIDER, ServerManager::BW), function (Player $player, bool $value): void {
                    $this->setValue($player, self::BW_SHOP_SLIDER, $value, ServerManager::BW);

                    if ($value) {
                        $player->sendMessage(TextFormat::GREEN . 'Enabled the shop slider. You will now be able to select an amount when purchasing from the shop.');
                    } else {
                        $player->sendMessage(TextFormat::RED . 'Disabled the shop slider. You will no longer be able to select an amount when purchasing from the shop.');
                    }
                }));
            }

            $form->addElement(new Label(TextFormat::GOLD . 'Conquests'));
            $form->addElement(new Toggle('Chest UI', $chestUI = $this->getBool($player, self::CQ_CHEST_UI, ServerManager::CQ), function (Player $player, bool $value): void {
                $this->setValue($player, self::CQ_CHEST_UI, $value, ServerManager::CQ);

                if ($value) {
                    $player->sendMessage(TextFormat::GREEN . 'Enabled the Conquests chest UI. You will now be shown a chest inventory interface when using the shop and upgrader.');
                } else {
                    $player->sendMessage(TextFormat::RED . 'Disabled the Conquests chest UI. You will now be shown a form interface when using the shop and upgrader.');
                }
            }));
            if (!$chestUI) {
                $form->addElement(new Toggle('Shop Slider', $this->getBool($player, self::CQ_SHOP_SLIDER, ServerManager::CQ), function (Player $player, bool $value): void {
                    $this->setValue($player, self::CQ_SHOP_SLIDER, $value, ServerManager::CQ);

                    if ($value) {
                        $player->sendMessage(TextFormat::GREEN . 'Enabled the shop slider. You will now be able to select an amount when purchasing from the shop.');
                    } else {
                        $player->sendMessage(TextFormat::RED . 'Disabled the shop slider. You will no longer be able to select an amount when purchasing from the shop.');
                    }
                }));
            }

            $form->sendForm();
        }
    }

    /**
     * @param Player $player
     * @param int $id
     * @param string $serverType
     * @return bool
     */
    public function getBool(Player $player, int $id, string $serverType = ''): bool
    {
        return (bool)$this->getValue($player, $id, $serverType);
    }

    /**
     * @param Player $player
     * @param int $id
     * @param string $serverType
     * @return int
     */
    public function getInt(Player $player, int $id, string $serverType = ''): int
    {
        return (int)$this->getValue($player, $id, $serverType);
    }
}