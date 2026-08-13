<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libminigames\settings;

use Closure;
use Exception;
use libforms\CustomForm;
use libforms\elements\Dropdown;
use libforms\elements\Element;
use libforms\elements\Input;
use libforms\elements\Slider;
use libforms\elements\Toggle;
use libminigames\Arena;
use libminigames\settings\components\Component;
use libminigames\TeamArena;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_merge;

trait SettingsTrait
{
    /** @var SettingsHolder[] */
    private array $holders = [];
    /** @phpstan-var (callable(Arena): (Element | null))[] */
    private array $elements = [];
    private bool $paused = false;
    private bool $allowTeamChanging = false;

    public function __construct()
    {
        $this->initialize();
        $this->initializeObservers();
    }

    public function initialize(): void
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            $descriptionAttribute = $property->getAttributes(SettingsDescription::class)[0] ?? null;
            $componentAttribute = $property->getAttributes(Component::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

            $description = $descriptionAttribute?->newInstance();
            $component = $componentAttribute?->newInstance();
            if ($description instanceof SettingsDescription && $component instanceof Component) {
                $this->holders[$property->getName()] = new SettingsHolder(
                    instance: $this,
                    description: $description,
                    component: $component,
                    property: $property
                );
            }
        }
    }

    public function initializeObservers(): void
    {
        $class = new ReflectionClass($this);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $observerAttribute = $method->getAttributes(SettingsObserver::class)[0] ?? null;

            $observer = $observerAttribute?->newInstance();
            if ($observer instanceof SettingsObserver) {
                // Check if property exists
                $property = $this->holders[$observer->getProperty()] ?? throw new RuntimeException("Property {$observer->getProperty()} not found");
                // Register observer using the method's block
                $property->registerObserver($method->getClosure($this));
            }
        }
    }

    /**
     * Registers an observer to be called when the property is updated.
     *
     * @param string $property - The property to associate the closure with
     * @param Closure(mixed, mixed): void $closure
     * @return void
     */
    public function registerObserver(string $property, Closure $closure): void
    {
        $holder = $this->holders[$property] ?? throw new RuntimeException("Property $property does not exist");
        $holder->registerObserver($closure);
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    public function setPaused(bool $paused): void
    {
        $this->paused = $paused;
    }

    /**
     * @return bool
     */
    public function isTeamChangingAllowed(): bool
    {
        return $this->allowTeamChanging;
    }

    /**
     * @param bool $allowed Allow players to change teams
     * @return void
     */
    public function setTeamChangingAllowed(bool $allowed): void
    {
        $this->allowTeamChanging = $allowed;
    }

    /**
     * This method returns a custom form that can be used
     * to send a game's settings to a player.
     *
     * @param string $title
     * @param Player $toSend
     * @return CustomForm
     */
    public function asForm(Arena $arena, string $title, Player $toSend): CustomForm
    {
        $form = new CustomForm(player: $toSend);

        $wasPaused = $arena->getGameSettings()->isPaused();

        $arena->getGameSettings()->setPaused(true);

        $form->setCloseClosure(function (Player $player) use ($arena, $wasPaused): void {
            $arena->getGameSettings()->setPaused($wasPaused);
        });

        $form->setSubmitClosure(function (Player $player): void {
            if ($player->isConnected()) {
                Translator::sendMessage($player, "game.settings.updated", Translator::TYPE_INFO);
            }
        });
        $form->setTitle($title);

        $form->addElement(new Toggle(
            text: "Pause game",
            default: $this->isPaused(),
            callable: function (Player $player, bool $value) use ($arena): void {
                if (!$arena->isWaiting()) {
                    return;
                }

                $this->setPaused($value);
            }
        ));
        foreach ($this->getElements($arena) as $element) {
            $form->addElement($element);
        }

        if ($arena instanceof TeamArena) {
            $form->addElement(new Toggle(
                "Allow Team Switching",
                $this->isTeamChangingAllowed(),
                function (Player $player, bool $value) use ($arena): void {
                    if (!$arena->isWaiting()) {
                        return;
                    }

                    $this->setTeamChangingAllowed($value);
                }
            ));
        }

        return $form;
    }

    /**
     * @param callable(Arena): Element $callable
     * @return void
     */
    public function addElement(callable $callable): void
    {
        $this->elements[] = $callable;
    }

    /**
     * @return Element[]
     */
    public function getElements(Arena $arena): array
    {
        /** @var (Element | null)[] $elements */
        $elements = array_map(fn(callable $callable) => $callable($arena), $this->elements);
        /** @var Element[] $nonNullElements */
        $nonNullElements = array_filter($elements, fn(?Element $element) => $element !== null);

        return array_merge(
            array_map(fn(SettingsHolder $setting) => $setting->asElement($arena), $this->holders),
            $nonNullElements
        );
    }

    /**
     * This method fetches the game configuration from the player's settings
     * and returns
     *
     * @param Player $player
     * @param string $serverType
     * @param string $gameType
     * @return static|null
     * @throws ReflectionException
     */
    public function fetchFromPlayer(Player $player, string $serverType, string $gameType): ?static
    {
        /*$data = NGEssentials::getInstance()->getPlayerData()->getArray(
            player: $player,
            id: PlayerData::GAME_CONFIGURATIONS
        )[self::createKeyFromTypes($serverType, $gameType)] ?? [];
        // If there is data that exists, parse it from the JSON, otherwise, return a new instance
        return count($data) > 0 ? self::fromArray($data) : null;*/
        return null;
    }

    /**
     * This method will create a key from the server type and game type.
     * This is used as a way to save and retrieve specific configurations from the player's data.
     *
     * @param string $serverType
     * @param string $gameType
     * @return string
     */
    private static function createKeyFromTypes(string $serverType, string $gameType): string
    {
        return $serverType . "_" . $gameType;
    }

    /**
     * This method will take a json-encoded string and return a new instance
     *
     * @param array<string, mixed> $decoded
     * @return static
     * @throws ReflectionException
     */
    public static function fromArray(array $decoded): static
    {
        $class = new ReflectionClass(static::class);
        /** @var static $instance */
        $instance = $class->newInstanceWithoutConstructor();
        foreach ($decoded as $key => $value) {
            try {
                $property = $class->getProperty($key);
                $property->setValue($instance, $value);
            } catch (Exception) {
                continue;
            }
        }
        return $instance;
    }

    /**
     * This method will update the player's game configuration to match that of the current state.
     *
     * @param Player $player
     * @param string $serverType
     * @param string $gameType
     * @return void
     */
    public function saveToPlayer(Player $player, string $serverType, string $gameType): void
    {
        $playerData = NGEssentials::getInstance()->getPlayerData();
        // Fetches all game configurations for the player
        $data = $playerData->getArray(player: $player, id: PlayerData::GAME_CONFIGURATIONS);
        // Updates the current game configuration
        $data[self::createKeyFromTypes($serverType, $gameType)] = $this->asArray();
        // Saves the game configurations back to the player's data
        $playerData->setValue(
            player: $player,
            id: PlayerData::GAME_CONFIGURATIONS,
            value: $data
        );
    }

    /**
     * This method will send an announcement to all players in the arena
     *
     * @param Arena $arena
     * @return void
     */
    public function sendSettingsAnnouncement(Arena $arena): void
    {
        if (count($elements = $this->getElements($arena)) === 0) {
            return;
        }

        $arena->broadcastMessage(TextFormat::GOLD . "Custom settings have been applied to this game!", true);
        foreach ($elements as $element) {
            $arena->broadcastMessage(TextFormat::GOLD . $element->getText() . " §r§l»§r " . match (true) {
                    $element instanceof Toggle => $element->getDefault() ? TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled",
                    $element instanceof Dropdown => TextFormat::AQUA . $element->getOptions()[$element->getDefault()],
                    $element instanceof Input => TextFormat::AQUA . $element->getDefault(),
                    $element instanceof Slider => TextFormat::AQUA . $element->getDefault(),
                    default => "Unknown"
                }, true);
        }
    }

    /**
     * This method will give you a json-encoded array of key-value pairs
     * This is useful for saving settings to a file, a database, etc.
     *
     * @return array<string, mixed>
     */
    public function asArray(): array
    {
        /** @var array<string, mixed> $array */
        $array = [];

        foreach ($this->holders as $holder) {
            [$name, $value] = $holder->asArray();
            $array[(string)$name] = $value;
        }

        return $array;
    }
}