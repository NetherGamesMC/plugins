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

namespace NetherGames\NGEssentials\player\cosmetics\types\game;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use NetherGames\NGEssentials\block\custom\CustomBed;
use NetherGames\NGEssentials\player\cosmetics\traits\CustomBlockCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use pocketmine\block\Bed;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo as Info;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;

class BedsCosmetic extends Cosmetic
{
    use CustomBlockCosmeticTrait;

    private function getBlockFunction(string $blockName, ?string $blockTexture, ?string $blockGeometry): Closure
    {
        return static fn() => new CustomBed(
            new BID(BlockTypeIds::newId()),
            $blockName,
            new Info(BreakInfo::pickaxe(0.2, ToolTier::WOOD, 30.0)),
            $blockTexture,
            $blockGeometry
        );
    }

    /**
     * @param Player[] $players
     */
    public function get(array $players, int $teamId): ?Bed
    {
        return ($entry = $this->getRandomSelectedEntry($players)) === null ? null : $this->getBedForTeam($entry, $teamId);
    }

    public function getBedForTeam(CosmeticEntry $entry, int $teamId): Bed
    {
        /** @var Bed $block */
        $block = $this->getCustomBlock($entry->getDataEntry($teamId));

        return $block;
    }

    /**
     * Replaces the current bed with the cosmetic of the given id.
     *
     * @pre Bed must be a valid bed.
     * @pre Id must be a valid id.
     */
    public function replaceBed(Bed $oldBed, Bed $newBed): void
    {
        /** @var Bed $part */
        foreach ([$oldBed, $oldBed->getOtherHalf()] as $part) {
            $newBed->setHead($part->isHeadPart());
            $newBed->setOccupied($part->isOccupied());
            $newBed->setFacing($part->getFacing());
            $newBed->setColor($part->getColor());

            $partPos = $part->getPosition();
            $partPos->getWorld()->setBlock($partPos, $newBed);
        }
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.flag'; // todo: unique animation
    }

    public function getName(): string
    {
        return 'Bed';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/bed_red', $callable);
    }
}