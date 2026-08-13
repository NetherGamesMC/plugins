<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);


namespace NetherGames\NGEssentials\player;

use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\FormManager;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\forms\Forms;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as TF;
use function str_replace;

/**
 * Simple guide for Translator class.
 * <p>
 * {@link Translator::sendMessage()} should be used if we're going to send a message to a player in their locale.
 * </p>
 * <p>
 * {@link Translator::getTranslationPlayer()} should only be used if no message will be sent to player. (For scoreboards, forms etc...)
 * </p>
 * <p>
 * {@link Translator::getTranslation()} should only be used where NGPlayer cannot be reached.
 * </p>
 */
class Translator
{
    public const TYPE_DEFAULT = 0;
    public const TYPE_INFO = 1;
    public const TYPE_WARNING = 2;
    public const TYPE_SUCCESS = 3;
    public const TYPE_ERROR = 4;

    private const PREFIXES = [
        self::TYPE_DEFAULT => "",
        self::TYPE_INFO => TF::BOLD . "» " . TF::RESET,
        self::TYPE_WARNING => TF::BOLD . "» " . TF::RESET,
        self::TYPE_SUCCESS => TF::BOLD . "» " . TF::RESET,
        self::TYPE_ERROR => TF::BOLD . "» " . TF::RESET,
    ];

    private const BASE_COLORS = [
        self::TYPE_DEFAULT => TF::WHITE,
        self::TYPE_INFO => TF::AQUA,
        self::TYPE_WARNING => TF::YELLOW,
        self::TYPE_SUCCESS => TF::GREEN,
        self::TYPE_ERROR => TF::RED,
    ];

    private const ARGUMENT_COLORS = [
        self::TYPE_DEFAULT => TF::WHITE,
        self::TYPE_INFO => TF::WHITE,
        self::TYPE_WARNING => TF::RED,
        self::TYPE_SUCCESS => TF::WHITE,
        self::TYPE_ERROR => TF::WHITE,
    ];

    public const TRANSLATE_API_URL = "https://translate-api.nethergames.org/translation";
    public const FALLBACK_LANGUAGE = "en_au";

    private static array $translations = [];

    public static function init(): void
    {
        self::pullTranslations();
    }

    public static function pullTranslations(): void
    {
        NGEssentials::getInstance()->getLogger()->info("Translation pulls are currently unavailable.");
    }

    public static function sendLanguageCredits(NGPlayer $player, NGEssentials $ess, string $language): void
    {
        $form = FormManager::createModalForm($player);

        if ($form !== null) {
            $form->setTitle(self::$translations[$language]['pretty_name']);
            $form->setContent(TextFormat::YELLOW . 'Are you sure that you want to select ' . self::$translations[$language]['pretty_name'] . ' as your preferred language?' . TextFormat::EOL);

            $form->setButton1(new Button(TextFormat::BOLD . TextFormat::GREEN . 'Yes', static function (NGPlayer $player) use ($ess, $language) {
                $player->setNGLanguage($language);
                $ess->getPlayerData()->setValue($player, PlayerData::LOCALE, $language, true);
                $player->sendMessage('§aSelected §6' . self::$translations[$language]['pretty_name'] . ' §aas your preferred language. Applicable messages will now be displayed using this locale.');
            }));

            $form->setButton2(new Button(TextFormat::BOLD . TextFormat::RED . 'No', static function (NGPlayer $player) use ($ess) {
                self::sendLanguageSelector($player, $ess, static function (NGPlayer $player) use ($ess) {
                    Forms::sendSettings($player, $ess);
                });
            }));

            $form->sendForm();
        }
    }

    /**
     * This function can be used for sending messages to a player in their locale.
     * If we are going to send arguments, we should send them as multiple parameters.
     * Example: <code> ...["player" => $player->getName(), "level" => $level] </code>
     * @param Player $player
     * @param string $translationKey
     * @param int $messageType
     * @param string ...$args
     */
    public static function sendMessage(Player $player, string $translationKey, int $messageType = self::TYPE_DEFAULT, string ...$args): void
    {
        /** @var NGPlayer $player */
        $message = self::getTranslationPlayer($player, $translationKey, $messageType, ...$args);

        if ($message === $translationKey) {
            $player->sendMessage("Translation " . $translationKey . " globally missing.");
            return;
        }

        $player->sendMessage(self::PREFIXES[$messageType] . $message);
    }

    /**
     * This function should only be used if no message will be sent to player. (For scoreboards, forms etc...)
     * Otherwise: {@link Translator::sendMessage}
     * @param Player $player
     * @param string $translationKey
     * @param int $messageType
     * @param string ...$args
     * @return string
     */
    public static function getTranslationPlayer(Player $player, string $translationKey, int $messageType = self::TYPE_DEFAULT, ...$args): string
    {
        /** @var NGPlayer $player */
        return self::getTranslation($player->getNGLanguage(), $translationKey, $messageType, ...$args);
    }

    /**
     * This function should only be used where NGPlayer cannot be reached.
     * Otherwise: {@link Translator::getTranslationPlayer}
     * @param string $language
     * @param string $translationKey
     * @param int $messageType
     * @param string ...$args
     * @return string
     */
    public static function getTranslation(string $language, string $translationKey, int $messageType = self::TYPE_DEFAULT, ...$args): string
    {
        $message = self::getRawTranslation($language, $translationKey);

        if ($message !== null) {
            $message = TF::RESET . self::BASE_COLORS[$messageType] . $message;
            foreach ($args as $index => $argument) {
                if ($messageType === self::TYPE_DEFAULT) {
                    $message = str_replace("{%$index}", $argument, $message);
                } else {
                    $message = str_replace("{%$index}", TF::RESET . self::ARGUMENT_COLORS[$messageType] . $argument . TF::RESET . self::BASE_COLORS[$messageType], $message);
                }
            }

            $message = str_replace("§§a", self::ARGUMENT_COLORS[$messageType], $message);
            $message = str_replace("§§m", self::BASE_COLORS[$messageType], $message);

            return $message;
        }

        return $translationKey; //if the translation key not exists, return translation key instead of null
    }

    private static function getRawTranslation(string $language, string $translationKey): ?string
    {
        if (array_key_exists($language, self::$translations) && array_key_exists($translationKey, self::$translations[$language]['translations'])) {
            return self::$translations[$language]['translations'][$translationKey];
        }

        if ($language !== self::FALLBACK_LANGUAGE) { //use fallback language (probably english)
            return self::getRawTranslation(self::FALLBACK_LANGUAGE, $translationKey);
        }

        return null;
    }

    public static function sendLanguageSelector(NGPlayer $player, NGEssentials $ess, ?callable $onBack): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form !== null) {
            $form->setTitle('Language Selector');
            $form->setBackClosure($onBack);

            foreach (self::$translations as $language => $languageData) {
                if ($language === $player->getNGLanguage()) {
                    $form->addButton(new ImageButton(TextFormat::GREEN . self::$translations[$language]['pretty_name'], ImageButton::IMAGE_TYPE_URL, self::$translations[$language]['flag'], $onBack));
                } else {
                    $form->addButton(new ImageButton(TextFormat::YELLOW . self::$translations[$language]['pretty_name'], ImageButton::IMAGE_TYPE_URL, self::$translations[$language]['flag'], static function (NGPlayer $player) use ($ess, $language) {
                        self::sendLanguageCredits($player, $ess, $language);
                    }));
                }
            }

            $form->sendForm();
        }

    }

    public static function translateLocale(string $locale): string
    {
        return match (strtolower($locale)) {
            'pt_pt' => 'pt_br',
            'fr_ca' => 'fr_fr',
            'es_es' => 'es_mx',
            default => strtolower($locale),
        };
    }

    public static function getLanguageNames(): array
    {
        return array_map(function ($language): string {
            return $language['pretty_name'];
        }, self::$translations);
    }

    public static function getLanguageLocales(): array
    {
        return array_keys(self::$translations);
    }

    public static function getFlag(string $language): string
    {
        if (!array_key_exists($language, self::$translations)) return "";
        return self::$translations[$language]['flag'] ?? self::$translations[self::FALLBACK_LANGUAGE]['flag'];
    }
}