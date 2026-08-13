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
 * @author Driesboy
 *
 */
declare(strict_types=1);

namespace libminigames\utils;

use JsonException;
use libasynCurl\Curl;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\utils\InternetRequestResult;
use function json_decode;
use function str_replace;
use function strtolower;
use function strtoupper;
use const JSON_THROW_ON_ERROR;
use const nethergames\API_URI;

class LeaderboardData
{
    /** @var array<string, array{string, string}> */
    private array $data = [];

    /**
     * @param array<string> $modes
     * @param array<string> $types
     */
    public function __construct(protected array $modes, protected array $types = [])
    {
    }

    private function getColumnName(string $columnName, int $modeId = -1, int $typeId = -1): string
    {
        return strtolower($this->getDisplayName($columnName, $modeId, $typeId));
    }

    private function getDisplayName(string $content, int $modeId = -1, int $typeId = -1): string
    {
        if ($modeId !== -1) {
            $content = str_replace('*mode*', $this->modes[$modeId], $content);
        }
        if ($typeId !== -1) {
            $content = str_replace('*type*', $this->types[$typeId], $content);
        }

        return $content;
    }

    public function load(string $columnName, int $modeId = -1, int $typeId = -1, string $title = '', string $text = ''): void
    {
        $columnName = $this->getColumnName($columnName, $modeId, $typeId);
        $title = strtoupper($this->getDisplayName(strtolower($title), $modeId, $typeId));
        $text = $this->getDisplayName($text, $modeId, $typeId);

        $page = API_URI . '/v1/leaderboard?type=game&column=' . $columnName . '&limit=10';
        Curl::getRequest($page, 10, [], function (?InternetRequestResult $result) use ($columnName, $title, $text): void {
            if ($result !== null && $result->getCode() === 200) {
                try {
                    /** @var array<int, array{player: string, value: int}> $rows */
                    $rows = json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    return;
                }

                $text .= "§r\n";
                $rank = 0;
                foreach ($rows as ["player" => $player, "value" => $value]) {
                    $rank++;
                    if ($rank === 1) {
                        $text .= "\n" . CustomIcon::TROPHY_GOLD . '§r§7- §b' . $player . ' - §e' . $value;
                    } elseif ($rank === 2) {
                        $text .= "\n" . CustomIcon::TROPHY_SILVER . '§r§7- §b' . $player . ' - §e' . $value;
                    } elseif ($rank === 3) {
                        $text .= "\n" . CustomIcon::TROPHY_BRONZE . '§r§7- §b' . $player . ' - §e' . $value;
                    } else {
                        $text .= "\n" . '§e#' . $rank . ' §r§b' . $player . ' - §e' . $value;
                    }
                }
                $this->data[$columnName] = [$title, $text];
            }
        });
    }

    /**
     * @return array{string, string}
     */
    public function get(string $columnName, int $modeId = -1, int $typeId = -1): array
    {
        return $this->data[$this->getColumnName($columnName, $modeId, $typeId)] ?? ['', ''];
    }
}