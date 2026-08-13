<?php

declare(strict_types=1);

namespace survivalgames\task;

use pocketmine\block\Water;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use survivalgames\SGArena;

class PlayersTickTask extends Task
{

    /** @var SGArena */
    private SGArena $arena;
    /** @var int */
    private int $currentTick = 0;
    /** @var int[] */
    private array $lastDamageTick = [];

    public function __construct(SGArena $arena)
    {
        $this->arena = $arena;
    }

    public function onRun(): void
    {
        $currentTick = $this->currentTick++;
        if ($currentTick % 5 !== 0) {
            return;
        }

        if ($this->arena->getStatus() === SGArena::STATUS_FINISHING) {
            $this->getHandler()->cancel();

            return;
        }

        $border = $this->arena->getBorderManager();

        foreach ($this->arena->getPlayers() as $player) {
            $border->renderParticles($player);

            // ---------------- BORDER DAMAGE EVENT ----------------

            if (!$border->isInsideBorder($player->getPosition())) {
                $this->attack($player, 2.0, EntityDamageEvent::CAUSE_CONTACT);

                $midPoint = $this->arena->getMidpoint();
                $entityPos = $player->getPosition();

                if (($dist = $midPoint->maxPlainDistance($entityPos)) <= 4) {
                    goto acidicCheck;
                }

                // Add knockback only if the area of the game is > 5 blocks squared
                if ($border->getBorderSize() > 5) {
                    $midPoint->y = $entityPos->getY() + $dist;
                    $motion = $entityPos->subtractVector($midPoint)->normalize()->multiply(-0.75);

                    $player->setMotion($motion);
                }

                $player->sendTip(TextFormat::RED . "You are leaving the safezone area. Get back inside the border!");
            }

            // ---------------- ACIDIC WATER EVENT ----------------

            acidicCheck:
            if ($this->arena->isSpectator($player)) {
                continue;
            }

            if ($this->arena->hasFlags(SGArena::ACIDIC_WATER)) {
                if (!($player->getWorld()->getBlock($player->getPosition()) instanceof Water)) {
                    continue;
                }

                $this->attack($player, 1.0, EntityDamageEvent::CAUSE_CUSTOM);
            }

            // ---------------- POTION EFFECTS EVENT ----------------
        }
    }

    private function attack(Player $player, float $damage, int $type): void
    {
        $lastDamage = $this->lastDamageTick[$player->getName()] ?? $this->currentTick - 20;
        if (($this->currentTick - $lastDamage) >= 20) {
            $cause = $player->getLastDamageCause();

            $ev = new EntityDamageEvent($player, $type, $damage);
            $player->attack($ev);

            if ($cause !== null) {
                $player->setLastDamageCause($cause);
            }

            $this->lastDamageTick[$player->getName()] = $this->currentTick;
        }
    }
}