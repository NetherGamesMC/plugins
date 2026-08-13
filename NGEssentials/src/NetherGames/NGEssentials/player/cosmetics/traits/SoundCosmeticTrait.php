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

namespace NetherGames\NGEssentials\player\cosmetics\traits;

use InvalidArgumentException;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticDataEntry;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;

/**
 * @mixin Cosmetic
 */
trait SoundCosmeticTrait
{
    private const LEVEL_EVENT_TYPE = 0;
    private const LEVEL_SOUND_EVENT_TYPE = 1;
    private const PLAY_SOUND_TYPE = 2;

    private const SOUND_KEY = 'sound';
    private const SOUND_TYPE_KEY = "type";
    private const SOUND_ID_KEY = 'id'; // int (LEVEL_EVENT_TYPE, LEVEL_SOUND_EVENT_TYPE, PLAY_SOUND_TYPE)
    private const SOUND_DATA_KEY = 'data'; // int (LEVEL_EVENT_TYPE, LEVEL_SOUND_EVENT_TYPE)
    private const SOUND_RELATIVE_VOLUME_KEY = 'relative_volume'; // bool (LEVEL_SOUND_EVENT_TYPE)
    private const SOUND_PITCH_KEY = 'pitch'; // float (PLAY_SOUND_TYPE)
    private const SOUND_VOLUME_KEY = 'volume'; // float (PLAY_SOUND_TYPE)

    public function run(Player $player, Position $pos): void
    {
        if (($entry = $this->getSelectedEntry($player)) !== null) {
            $pos->getWorld()->broadcastPacketToViewers($pos, $this->getSound($entry->getDataEntry(), $pos));
        }
    }

    protected function getSound(CosmeticDataEntry $entry, Position $pos): ClientboundPacket
    {
        $soundKey = $entry->data[self::SOUND_KEY] ?? throw new InvalidArgumentException("Sound key not found");

        return match ($soundKey[self::SOUND_TYPE_KEY] ?? throw new InvalidArgumentException("Sound type not found")) {
            self::LEVEL_EVENT_TYPE => LevelEventPacket::create(
                $soundKey[self::SOUND_ID_KEY] ?? throw new InvalidArgumentException("Unknown sound ID $entry->id"),
                $soundKey[self::SOUND_DATA_KEY] ?? 0,
                $pos
            ),
            self::LEVEL_SOUND_EVENT_TYPE => LevelSoundEventPacket::nonActorSound(
                LevelSoundEvent::toString($soundKey[self::SOUND_ID_KEY] ?? throw new InvalidArgumentException("Unknown sound ID $entry->id")),
                $pos,
                !($soundKey[self::SOUND_RELATIVE_VOLUME_KEY] ?? true),
                $soundKey[self::SOUND_DATA_KEY] ?? -1,
            ),
            self::PLAY_SOUND_TYPE => PlaySoundPacket::create(
                $soundKey[self::SOUND_ID_KEY] ?? throw new InvalidArgumentException("Unknown sound ID $entry->id"),
                $pos->getX(),
                $pos->getY(),
                $pos->getZ(),
                $soundKey[self::SOUND_VOLUME_KEY] ?? 1,
                $soundKey[self::SOUND_PITCH_KEY] ?? 1,
                null
            ),
            default => throw new InvalidArgumentException("Invalid sound type"),
        };
    }

    protected function onPlayerCosmeticSelect(Player $player): bool
    {
        $player->getNetworkSession()->sendDataPacket($this->getSound($this->getSelectedEntry($player)->getDataEntry(), $player->getPosition()));
        return false;
    }

    private function isSoundCosmeticEntry(CosmeticDataEntry $entry): bool
    {
        return isset($entry->data[self::SOUND_KEY]);
    }
}