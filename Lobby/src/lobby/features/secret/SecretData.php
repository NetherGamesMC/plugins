<?php
declare(strict_types=1);

namespace lobby\features\secret;

class SecretData
{
    public const VOLCANO = "Volcano";
    public const MAZE = "Maze";
    public const ARCHERY = "Archery";
    public const CAVE = "Cave";
    public const FESTIVAL = "Festival";
    public const CLIFF = "Cliff";

    public const SECRET_STANDS = [
        self::VOLCANO => [
            "name" => self::VOLCANO,
            "stand" => [
                "x" => 24.9,
                "y" => 25.5,
                "z" => 32.9,
            ],
        ],
        self::MAZE => [
            "name" => self::MAZE,
            "stand" => [
                "x" => 25.9,
                "y" => 25.5,
                "z" => 37.0,
            ],
        ],
        self::ARCHERY => [
            "name" => self::ARCHERY,
            "stand" => [
                "x" => 16,
                "y" => 25.5,
                "z" => 37,
            ],
        ],
        self::CAVE => [
            "name" => self::CAVE,
            "position" => [
                "x" => 39.9,
                "y" => 15.5,
                "z" => 164.9,
            ],
            "stand" => [
                "x" => 25.0,
                "y" => 25.5,
                "z" => 41.0,
            ],
        ],
        self::FESTIVAL => [
            "name" => self::FESTIVAL,
            "stand" => [
                "x" => 17,
                "y" => 25.5,
                "z" => 41,
            ],
        ],
        self::CLIFF => [
            "name" => self::CLIFF,
            "position" => [
                "x" => -181.5,
                "y" => 44.5,
                "z" => -109.5,
            ],
            "stand" => [
                "x" => 16.9,
                "y" => 25.5,
                "z" => 32.9,
            ],
        ],
    ];
}