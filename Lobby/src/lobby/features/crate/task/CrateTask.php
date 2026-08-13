<?php

declare(strict_types=1);

namespace lobby\features\crate\task;

use lobby\entity\custom\CrateEntity;
use lobby\features\crate\Crate;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use function array_rand;

class CrateTask extends Task
{
    private float $time = -6.5;
    private FloatingText $floatingText;

    /** @var array<int, string> */
    private static ?array $cosmeticAnimations = null;

    /** @var array<string, string> */
    private array $animations;

    public function __construct(
        private CrateEntity   $crateEntity,
        private Crate         $crate,
        private CosmeticEntry $entry
    )
    {
        $this->floatingText = $this->crate->getFloatingText();

        $world = $this->floatingText->getLocation()->getWorld();
        foreach ($world->getPlayers() as $player) {
            $this->floatingText->despawn($player);
        }

        /** @var array<int, string> $animations */
        $animations = self::$cosmeticAnimations ??= $this->generateCosmeticAnimations();

        $this->animations[(string)$this->time] = "animation.ng.lobby.crate.open";

        $time = 0;
        foreach (array_rand($animations, 6) as $randomAnimation) {
            $this->animations[(string)$time] = $animations[$randomAnimation] . ".short";
            $time += 1;
        }

        $this->animations[(string)$time] = $animations[$entry->type];
        $this->animations[(string)($time + 6)] = "animation.ng.lobby.crate.close";
    }

    private function generateCosmeticAnimations(): array
    {
        $animations = [];

        foreach (CosmeticHandler::getAll() as $cosmetic) {
            $animations[$cosmetic->getSaveId()] = $cosmetic->getCrateAnimation();
        }

        return $animations;
    }

    public function onRun(): void
    {
        if ($this->time >= 15 || ($player = $this->crate->getPlayer()) === null || !$player->isConnected()) {
            $this->getHandler()->cancel();

            return;
        }

        if ($this->time === 7.0) {
            $cosmeticName = $this->entry->name;
            $cosmeticTypeName = CosmeticHandler::getCosmeticById($this->entry->type)->getName();
            $rarityString = $this->entry->rarity->getColor() . $this->entry->rarity->getName();
            $string = $rarityString . ' ' . $cosmeticName . ' ' . TextFormat::BLUE . $cosmeticTypeName;

            $this->floatingText->setTitle(TextFormat::GOLD . "You found: " . $string);
            $this->floatingText->spawn($player);

            $this->floatingText->setTitle(TextFormat::GOLD . "{$player->getName()} found: " . $string);
            foreach ($player->getWorld()->getPlayers() as $p) {
                if ($p !== $player) {
                    $this->floatingText->spawn($p);
                }
            }
        }

        if (isset($this->animations[(string)$this->time])) {
            NetworkBroadcastUtils::broadcastPackets($player->getWorld()->getViewersForPosition($this->crate->asVector3()), [AnimateEntityPacket::create($this->animations[(string)$this->time], "", "", 0, "", 0, [$this->crateEntity->getId()])]);
        }

        $this->time += 0.5;
    }

    public function onCancel(): void
    {
        $this->floatingText->setTitle(TextFormat::GREEN . "Click to open a crate!");
        $this->floatingText->setText("");
        $this->floatingText->updateMetadata();

        $this->crate->setInUse(null);
    }
}