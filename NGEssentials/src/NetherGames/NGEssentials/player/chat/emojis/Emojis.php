<?php

namespace NetherGames\NGEssentials\player\chat\emojis;

use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\SingletonTrait;
use function array_keys;
use function array_map;
use function implode;
use function preg_quote;
use function preg_replace_callback;
use function str_replace;
use function strlen;
use function usort;

class Emojis
{
    use SingletonTrait;

    /** @var string[] */
    private array $emojis = [];
    /** @var string */
    private string $emojiPattern;

    public function __construct()
    {
        $this->register(CustomIcon::HEART_EYES, ["heart_eyes", "love_eyes"], ["😍", "<3", "♥", "❤"], false);
        $this->register(CustomIcon::FLUSHED, ["flushed", "flushed_face", "shame", "embarrassed"], ["😳"], false);
        $this->register(CustomIcon::SLIGHT_SMILE, ["slight_smile", "slightly_happy"], ['🙂', ':)', ':-)'], false);
        $this->register(CustomIcon::SWEAT_SMILE, ["sweat_smile", "happy_sweat"], ['😅', '^^"', "^^'", '^_^"', "^_^'"], false); //must be before ^^ because of replace
        $this->register(CustomIcon::BLUSH, ["blush"], ["😊"], false);
        $this->register(CustomIcon::GRIN, ["grin"], ["😀"], false);
        $this->register(CustomIcon::SMIRK, ["smirk", "smirking_face", "smug_face", "flirting"], ["😏", ";)", ";-)"], false); //replace if wink gets added
        $this->register(CustomIcon::RAGE, ["rage", "pouting_face", "pout"], ["😡", ">:(", ">:[", ">:-(", ">:-[", ">:c", ">:-c"], false);
        $this->register(CustomIcon::THINKING, ["thinking", "chin_thumb", "hmm"], ["🤔", ":?", ":-?"], false);
        $this->register(CustomIcon::THUMBS_UP, ["thumbs_up"], ["👍"], false);
        $this->register(CustomIcon::SCARED, ["scared"], ["😨"], false);
        $this->register(CustomIcon::SAD, ["sad", "worried_face", "worried", "sad_face"], ["😟", ":("], false);
        $this->register(CustomIcon::CRYING, ["sob", "sobbing", "sad_tears", "crying"], ["😭", ";-;", ":'(", ":'-(", ":'[", ":'-[", ":'c", ":'-c"], false);
        $this->register(CustomIcon::COOL, ["cool", "sunglasses"], ["😎", "8)", "B)", "8-)", "B-)"], false);
        $this->register(CustomIcon::TONGUE_OUT, ["tongue_out"], ['😝', ':p', ':-p', ':b', ':-b'], false);
        $this->register(CustomIcon::JOY, ["joy", "laughing", "lol", "lmao", "lmfao"], ["😂"], false);
        $this->register(CustomIcon::ROFL, ["rofl", "xd", "lmfao"], ["🤣", ">.<"], false);
        $this->register(CustomIcon::SKULL_EMOJI, ["skull"], ["💀"], false);
        $this->register(CustomIcon::VOMIT, ["vomit", "vomiting"], ["🤮"], false);
        $this->register(CustomIcon::EYES, ["eyes"], ["👀", "o_o"], false);
        $this->register(CustomIcon::SMILE_UPSIDE_DOWN, ["upside_down"], ["🙃"], false);
        $this->register(CustomIcon::GG, ["gg"], [], false);
        $this->register(CustomIcon::W, ["w"], [], false);
        $this->register(CustomIcon::MENDING_HEART, ["mending_heart"], ["❤️‍🩹"], false);
        $this->register(CustomIcon::ENVELOPE, ["envelope"], ["✉️"], false);
        $this->register(CustomIcon::SMILING_IMP, ["smiling_imp"], ["👿"], false);
        $this->register(CustomIcon::DIZZY, ["dizzy", "confused"], ["🥴"], false);
        $this->register(CustomIcon::INNOCENT, ["innocent"], ["😇"], false);
        $this->register(CustomIcon::NEUTRAL, ["neutral", "neutral_face"], ["😐"], false);
        $this->register(CustomIcon::DEAD, ["dead"], ["😵"], false);
        $this->register(CustomIcon::NERD, ["nerd"], ["🤓"]);
    }

    /**
     * @param string $emoji
     * @param string[] $aliases
     * @param string[] $replacements
     * @return void
     */
    public function register(string $emoji, array $aliases, array $replacements, bool $updatePattern = true): void
    {
        foreach ($aliases as $alias) {
            $this->emojis[':' . $alias . ':'] = $emoji;
        }

        foreach ($replacements as $replacement) {
            $this->emojis[$replacement] = $emoji;
        }

        if ($updatePattern) {
            $this->updatePattern();
        }
    }

    private function updatePattern(): void
    {
        $sortedEmojiKeys = array_keys($this->emojis);
        usort($sortedEmojiKeys, static fn(string $a, string $b) => strlen($b) <=> strlen($a));
        $this->emojiPattern = implode('|', array_map(fn(string $key) => preg_quote($key, '/'), $sortedEmojiKeys));
    }

    public function replace(string $message): string
    {
        return str_replace('\\:', ':', preg_replace_callback(
            '/(?<!\\\\)(' . $this->emojiPattern . ')/u',
            fn(array $m) => $this->emojis[$m[1]],
            $message
        ));
    }


    public function getEmojisForHelpMenu(): array
    {
        $result = [];
        $seen = [];
        foreach ($this->emojis as $replacement => $emoji) {
            if (!isset($seen[$emoji])) {
                $example = rtrim($replacement);
                $result[] = [$emoji, $example];
                $seen[$emoji] = true;
            }
        }
        return $result;
    }
}