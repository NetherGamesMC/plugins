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

namespace NetherGames\NGEssentials\player\chat\filter;

use Closure;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function similar_text;
use function str_ireplace;
use function str_replace;
use function strtolower;

class ChatFilter
{
    public const SEPARATOR_PLACEHOLDER = '{!!}';

    private const CHAT_COOLDOWN = 3 * 20;
    private const REPEAT_COOLDOWN = 10 * 20;

    /** @var string[] */
    public static array $domains = [
        '.me',
        '.net',
        '.code',
        '.cc',
        '.io',
        '.com',
        '.best',
        '.leet.cc',
        '.net',
        '.ddns',
        '.ddns.net',
        '.cf',
        '.pocket.pe',
        '.no-ip',
        '.live',
        '.ml',
        '.org',
        '.tk',
        '.gov'
    ];
    /**
     * Unescaped separator characters.
     * @var string[]
     */
    public static array $separatorCharacters = array(
        '@',
        '#',
        '%',
        '&',
        '_',
        ';',
        "'",
        '"',
        ',',
        '~',
        '`',
        '|',
        '!',
        '$',
        '^',
        '*',
        '(',
        ')',
        '-',
        '+',
        '=',
        '{',
        '}',
        '[',
        ']',
        ':',
        '<',
        '>',
        '?',
        '.',
        '/',
    );
    /**
     * List of potential character substitutions as a regular expression.
     *
     * @var string[][]
     */
    public static array $characterSubstitutions = array(
        '/a/' => array(
            'a',
            '4',
            '@',
            'Á',
            'á',
            'À',
            'Â',
            'à',
            'Â',
            'â',
            'Ä',
            'ä',
            'Ã',
            'ã',
            'Å',
            'å',
            'æ',
            'Æ',
            'α',
            'Δ',
            'Λ',
            'λ',
        ),
        '/b/' => array('b', '8', '\\', '3', 'ß', 'Β', 'β'),
        '/c/' => array('c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'),
        '/d/' => array('d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'),
        '/e/' => array('e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'),
        '/f/' => array('f', 'ƒ'),
        '/g/' => array('g', '6', '9'),
        '/h/' => array('h', 'Η'),
        '/i/' => array('i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'),
        '/j/' => array('j'),
        '/k/' => array('k', 'Κ', 'κ'),
        '/l/' => array('l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'),
        '/m/' => array('m'),
        '/n/' => array('n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'),
        '/o/' => array(
            'o',
            '0',
            'Ο',
            'ο',
            'Φ',
            '¤',
            '°',
            'ø',
            'ô',
            'Ô',
            'ö',
            'Ö',
            'ò',
            'Ò',
            'ó',
            'Ó',
            'œ',
            'Œ',
            'ø',
            'Ø',
            'ō',
            'Ō',
            'õ',
            'Õ',
        ),
        '/p/' => array('p', 'ρ', 'Ρ', '¶', 'þ'),
        '/q/' => array('q'),
        '/r/' => array('r', '®'),
        '/s/' => array('s', '5', '$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'),
        '/t/' => array('t', 'Τ', 'τ'),
        '/u/' => array('u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'),
        '/v/' => array('v', 'υ', 'ν'),
        '/w/' => array('w', 'ω', 'ψ', 'Ψ'),
        '/x/' => array('x', 'Χ', 'χ'),
        '/y/' => array('y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'),
        '/z/' => array('z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'),
    ); // used to stop players using nicks/pet names to impersonate staff
    protected static array $allowedWords = [
        "kill",
        "destroy",
    ];
    /**
     * Escaped separator characters
     * @var string[]
     */
    protected array $escapedSeparatorCharacters = [
        '\s',
    ];
    /**
     * List of expressions to test against.
     *
     * @var array
     */
    protected array $expressions = [];
    /** @var string */
    protected string $separatorExpression;
    /** @var array */
    protected array $characterExpressions;
    /** @var array */
    private array $chatData = [];

    public function __construct()
    {
        $this->separatorExpression = $this->generateSeparatorExpression();
        $this->characterExpressions = $this->generateCharacterExpressions();

        $servers = include __DIR__ . '/glossary/Servers.php';

        foreach ($servers as $profanity) {
            $this->expressions[] = $this->generateAdvertisementExpression($profanity, $this->characterExpressions, $this->separatorExpression);
        }
    }

    /**
     * Generates the separator regular expression.
     *
     * @return string
     */
    private function generateSeparatorExpression(): string
    {
        return $this->generateEscapedExpression(self::$separatorCharacters, $this->escapedSeparatorCharacters);
    }

    /**
     * Generates the separator regex to test characters in between letters.
     *
     * @param array $characters
     * @param array $escapedCharacters
     * @param string $quantifier
     *
     * @return string
     */
    private function generateEscapedExpression(array $characters = array(), array $escapedCharacters = array(), string $quantifier = '*?'): string
    {
        $regex = $escapedCharacters;
        foreach ($characters as $character) {
            $regex[] = preg_quote($character, '/');
        }
        return '[' . implode('', $regex) . ']' . $quantifier;
    }

    /**
     * Generates a list of regular expressions for each character substitution.
     *
     * @return array
     */
    protected function generateCharacterExpressions(): array
    {
        $characterExpressions = array();
        foreach (self::$characterSubstitutions as $character => $substitutions) {
            $characterExpressions[$character] = $this->generateEscapedExpression(
                    $substitutions,
                    array(),
                    '+?'
                ) . self::SEPARATOR_PLACEHOLDER;
        }
        return $characterExpressions;
    }

    /**
     * Generate a regular expression for a particular word
     *
     * @param string $word
     * @param array $characterExpressions
     * @param string $separatorExpression
     *
     * @return string
     */
    protected function generateAdvertisementExpression(string $word, array $characterExpressions, string $separatorExpression): string
    {
        $expression = '/' . preg_replace(
                array_keys($characterExpressions),
                array_values($characterExpressions),
                $word
            ) . '/i';
        return str_replace(self::SEPARATOR_PLACEHOLDER, $separatorExpression, $expression);
    }

    public static function setAllowedWords(array $words): void
    {
        self::$allowedWords = $words;
    }

    public function checkAdvertising(Player $player, string $message): bool
    {
        if ($this->containsAdvertising($message)) {
            $player->sendMessage(TextFormat::RED . 'Advertising is not allowed.');
            return false;
        }

        return true;
    }

    /**
     * Checks string for expressions based on list 'expressions'
     *
     * @param string $string
     *
     * @return bool
     */
    public function containsAdvertising(string $string): bool
    {
        if ($string === '') {
            return false;
        }

        $editedMessage = str_ireplace(self::$allowedWords, "", $string);

        if (!str_contains($editedMessage, 'nethergames.org') && !str_contains($editedMessage, 'ngmc.co')) {
            foreach ($this->expressions as $profanity) {
                if (preg_match($profanity, $editedMessage) === 1) {
                    return true;
                }
            }

            $parts = explode('.', $editedMessage);

            if (count($parts) >= 2) {
                foreach ($parts as $part) {
                    foreach (self::$domains as $domain) {
                        if (str_contains('.' . strtolower($part), $domain)) {
                            return true;
                        }
                    }
                }
            }
        }

        return preg_match('/(\b25[0-5]|\b2[0-4]\d|\b[01]?\d\d?)(\s*\.\s*(25[0-5]|2[0-4]\d|[01]?\d\d?)){3}/', $editedMessage) === 1;
    }

    public function checkSpam(Player $player, string $message): bool
    {
        $tick = $player->getServer()->getTick();
        $message = strtolower(trim($message));

        if (isset($this->chatData[$player->getName()])) {
            [$previousTick, $previousMessage] = $this->chatData[$player->getName()];

            if (($tick - $previousTick) < self::REPEAT_COOLDOWN && !($message === "gg" && $previousMessage === "gg")) {
                similar_text($message, $previousMessage, $percent);
                if ($percent > 80) {
                    $player->sendMessage(TextFormat::RED . "Don't repeat yourself!");
                    return false;
                }
            }

            if ($player->hasPermission(Permissions::RANK_ULTRA)) {
                return true;
            }

            if (($tick - $previousTick) < self::CHAT_COOLDOWN) {
                $player->sendMessage(TextFormat::RED . "Calm down, don't chat too quickly!");
                return false;
            }
        }

        $this->chatData[$player->getName()] = [$tick, $message];
        return true;
    }

    public function checkImpersonation(Player $player, string $message): bool
    {
        // implementation required
        return true;
    }

    public function checkSwearing(Player $player, string $message, Closure $onValid): void
    {
        // implementation required
        $onValid();
    }

    public function unsetChatData(Player $player): void
    {
        unset($this->chatData[$player->getName()]);
    }
}
