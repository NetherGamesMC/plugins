<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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

namespace libVanilla\session;

use libVanilla\network\types\DimensionType;
use pocketmine\player\Player;

final class PlayerSession
{
    protected int $enchantmentSeed;
    protected DimensionType $type;

    public function __construct(protected Player $player, protected int $protocolId = -1)
    {
        $this->enchantmentSeed = mt_rand();
        $this->type = DimensionType::OVERWORLD;
    }

    public static function create(Player $player): self
    {
        return new PlayerSession($player, $player->getNetworkSession()->getProtocolId());
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    public function getEnchantmentSeed(): int
    {
        return $this->enchantmentSeed;
    }

    public function setEnchantmentSeed(int $enchantmentSeed): void
    {
        $this->enchantmentSeed = $enchantmentSeed;
    }

    public function getDimensionType(): DimensionType
    {
        return $this->type;
    }

    public function setDimensionType(DimensionType $type): void
    {
        $this->type = $type;
    }

}