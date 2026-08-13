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

namespace NetherGames\NGEssentials\entity\custom;

use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\utils\TextFormat;
use function str_repeat;

class FloatingText extends CustomHuman
{

    public function __construct(Location $location, private string $title, private string $text = '')
    {
        parent::__construct($location, $this->getUsername(), new Skin('Standard_Custom', str_repeat("\x00", 64 * 64 * 4)));
        $this->metadata->setFloat(EntityMetadataProperties::SCALE, 0.01);
        $this->metadata->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
    }

    public function getUsername(): string
    {
        return $this->getTitle() . (($text = $this->getText()) === '' ? '' : TextFormat::EOL . $text);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;

        $this->updateNametag();
    }

    public function setText(string $text): void
    {
        $this->text = $text;

        $this->updateNametag();
    }
}