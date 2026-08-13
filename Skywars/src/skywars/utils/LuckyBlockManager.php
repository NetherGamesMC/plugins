<?php

declare(strict_types=1);

namespace skywars\utils;

use NetherGames\NGEssentials\utils\SkinUtils;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use skywars\entities\LuckyBlock;
use skywars\SWArena;
use Symfony\Component\Filesystem\Path;

class LuckyBlockManager
{
    /** @var Entity[] $entities */
    private array $luckyBlocks = [];
    /** @var SWArena */
    private SWArena $arena;

    public function __construct(SWArena $arena)
    {
        $this->arena = $arena;
    }

    /**
     * @return LuckyBlock[]
     */
    public function purgeLuckyBlocks(): array
    {
        $luckyBlocks = [];

        foreach ($this->luckyBlocks as $index => $luckyBlock) {
            /** @var LuckyBlock $luckyBlock */
            if ($luckyBlock->isClosed()) {
                $luckyBlocks [] = $luckyBlock;
                unset($this->luckyBlocks[$index]);
            }
        }

        return $luckyBlocks;
    }

    public function killLuckyBlocks(): void
    {
        foreach ($this->luckyBlocks as $luckyBlock) {
            /** @var LuckyBlock $luckyBlock */
            if (!$luckyBlock->isClosed()) {
                $luckyBlock->close();
            }
        }
    }

    public function createLuckyBlock(Location $location): LuckyBlock
    {
        $texture = SkinUtils::getTextureFromResources(Path::join('skins', 'objects', 'luckyblock', 'luckyblock.png'));
        $resource = $this->arena->getPlugin()->getEssentials()->getResource(Path::join('skins', 'objects', 'luckyblock', 'luckyblock.json'));

        $geometry = stream_get_contents($resource);
        fclose($resource);

        $skinTag = CompoundTag::create();

        $skinTag->setString("Name", "Standard_Custom");
        $skinTag->setByteArray("Data", $texture);

        $skin = new Skin('Standard_Custom', $texture, '', 'geometry.luckyblock', $geometry);
        return $this->luckyBlocks[] = new LuckyBlock($location, $skin, CompoundTag::create()->setTag("Skin", $skinTag), $this->arena);
    }
}