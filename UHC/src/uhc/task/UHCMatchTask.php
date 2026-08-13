<?php

declare(strict_types=1);

namespace uhc\task;

use libminigames\tasks\MatchTimeTask;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\utils\CustomIcon;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use uhc\game\UHCArena;
use uhc\utils\StatsData;

class UHCMatchTask extends MatchTimeTask
{
    private const PVP_TIME = 6;
    private const FINAL_HEAL_TIME = 3;

    public function gameTick(): void
    {
        /** @var UHCArena $arena */
        $arena = $this->getArena();
        /** @var NGPlayer $player */
        foreach ($arena->getAlivePlayers() as $player) {
            $player->setHealthTag();
            $arena->getBorder()->renderParticles($player);
            if (!$arena->getBorder()->isPlayerInsideOfBorder($player)) {
                $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_CUSTOM, 1));
            }

            if ($this->timePassed === self::FINAL_HEAL_TIME * 60) {
                $player->setHealth($player->getMaxHealth());
                $this->getArena()->broadcastMessage("§eFinal heal has occurred!");
            }
        }

        if (!$arena->isPvPEnabled()) {
            $this->handleGrace();
        } else {
            $this->handlePvP();
        }

        $arena->getScoreboard()->setLine($arena->getPlayers(), UHCArena::LINE_TIMER, CustomIcon::HOURGLASS . "§a" . gmdate("H:i:s", $this->timePassed));
    }

    private function handleGrace(): void
    {
        $pvpTimeInSeconds = self::PVP_TIME * 60;

        /** @var UHCArena $arena */
        $arena = $this->getArena();
        switch ($this->timePassed) {
            case 10:
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §a" . self::PVP_TIME . "§e minutes.", true);
                $this->getArena()->broadcastMessage("§eFinal heal will occur in §a" . self::FINAL_HEAL_TIME . "§e minutes.");
                break;
            case (self::PVP_TIME - 3) * 60:
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §a3§e minutes.", true);
                break;
            case (self::PVP_TIME - 1) * 60:
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §a1§e minute.", true);
                break;
            case $pvpTimeInSeconds - 30:
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §a30§e seconds.", true);
                break;
            case $pvpTimeInSeconds - 10:
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §a10§e seconds.", true);
                break;
            case $pvpTimeInSeconds - 5:
            case $pvpTimeInSeconds - 4:
            case $pvpTimeInSeconds - 3:
            case $pvpTimeInSeconds - 2:
            case $pvpTimeInSeconds - 1:
                $remainingTime = $pvpTimeInSeconds - $this->timePassed;
                $this->getArena()->broadcastMessage("§ePvP will be enabled in §c$remainingTime §esecond(s).", true);
                break;
            case $pvpTimeInSeconds:
                $arena->setPvPEnabled(true);
                $this->getArena()->broadcastMessage("§ePvP has been enabled!");
                break;
        }
    }

    private function handlePvP(): void
    {
        $pvpTimeInSeconds = self::PVP_TIME * 60;
        $secondShrink = $pvpTimeInSeconds + 250;
        $thirdShrink = $secondShrink + 250;
        $fourthShrink = $thirdShrink + 250;
        $fifthShrink = $fourthShrink + 150;
        $sixthShrink = $fifthShrink + 50;
        $seventhShrink = $sixthShrink + 25;

        /** @var UHCArena $arena */
        $arena = $this->getArena();
        switch ($this->timePassed) {
            case $pvpTimeInSeconds:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c750§e!");
                break;
            case $secondShrink:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c500§e!");
                break;
            case $thirdShrink:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c250§e!");
                break;
            case $fourthShrink:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c100§e!");
                break;
            case $fifthShrink:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c50§e!");
                break;
            case $sixthShrink:
                $this->getArena()->broadcastMessage("§eThe border is shrinking to §c25§e!");
                break;
        }

        if ($this->timePassed >= $pvpTimeInSeconds && $this->timePassed < $secondShrink) {
            $arena->getBorder()->shrinkTo(750, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c750§e!");
            });
        } elseif ($this->timePassed >= $secondShrink && $this->timePassed < $thirdShrink) {
            $arena->getBorder()->shrinkTo(500, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c500§e!");
            });
        } elseif ($this->timePassed >= $thirdShrink && $this->timePassed < $fourthShrink) {
            $arena->getBorder()->shrinkTo(250, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c250§e!");
            });
        } elseif ($this->timePassed >= $fourthShrink && $this->timePassed < $fifthShrink) {
            $arena->getBorder()->shrinkTo(100, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c100§e!");
            });
        } elseif ($this->timePassed >= $fifthShrink && $this->timePassed < $sixthShrink) {
            $arena->getBorder()->shrinkTo(50, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c50§e!");
            });
        } elseif ($this->timePassed >= $sixthShrink && $this->timePassed <= $seventhShrink) {
            $arena->getBorder()->shrinkTo(25, function () use ($arena) {
                $arena->broadcastMessage("§eThe border has shrunk to §c25§e!");
            });
        }
    }

    public function finishArena(): void
    {
        /** @var UHCArena $arena */
        $arena = $this->getArena();
        $team = $arena->getAliveTeams()[0] ?? null;

        if ($team !== null) {
            $statsData = $arena->getStatsData();

            foreach ($team->getXuids() as $xuid) {
                $statsData->addValue($xuid, StatsData::WINS);
                $statsData->addValue($xuid, StatsData::UHC_WINS);
            }

            foreach ($team->getPlayers() as $player) {
                if ($arena->isSoloGame()) {
                    $player->sendTitle("§l§6VICTORY!", "§7You were the last player standing!");
                } else {
                    $player->sendTitle("§l§6VICTORY!", "§7You were the last team standing!");
                }
            }
        }

        parent::finishArena();
    }

    public function getPlayingTime(): int
    {
        return PHP_INT_MAX;
    }
}
