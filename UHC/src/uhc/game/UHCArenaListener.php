<?php

declare(strict_types=1);

namespace uhc\game;

use libminigames\Arena;
use libminigames\ArenaListener;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\game\scenario\base\ScenarioHandler;
use uhc\utils\DeathMessages;
use uhc\utils\StatsData;
use uhc\voting\Items;
use uhc\voting\UHCForms;
use function preg_replace;

class UHCArenaListener extends ArenaListener
{
    use ScenarioHandler {
        onBlockBreak as handlerBlockBreak;
        onEntityDamage as handlerEntityDamage;
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $arena = $this->getArena();
        $victim = $event->getEntity();
        $this->handlerEntityDamage($event);

        if (!($victim instanceof Player)) {
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent && !$arena->isPvPEnabled()) {
            $event->cancel();
        }

        if ($event instanceof EntityDamageByChildEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof NGPlayer) {
                $damager->playSound("random.orb");
            }
        }

        if (!$event->isCancelled() && $event->getFinalDamage() >= $victim->getHealth()) {
            $event->cancel();
            $arena->broadcastMessage(TextFormat::GRAY . DeathMessages::selectDeathMessage($event, $victim));
            $arena->onPlayerDeath($victim, $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null);
        }
    }

    /**
     * @return UHCArena
     */
    public function getArena(): Arena
    {
        /** @var UHCArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $arena = $this->getArena();
        $this->handlerBlockBreak($event);

        if (!$event->isCancelled()) {
            $player = $event->getPlayer();
            $statsId = match ($event->getBlock()->getTypeId()) {
                BlockTypeIds::IRON_ORE => StatsData::UHC_IRON_MINED,
                BlockTypeIds::GOLD_ORE => StatsData::UHC_GOLD_MINED,
                BlockTypeIds::LAPIS_LAZULI_ORE => StatsData::UHC_LAPIS_MINED,
                BlockTypeIds::DIAMOND_ORE => StatsData::UHC_DIAMOND_MINED,
                default => null,
            };

            if ($statsId !== null) {
                $arena->getStatsData()->addValue($player, $statsId);
            }
        }
    }

    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void
    {
        if ($event->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
            $event->cancel();
        }
    }

    public function onItemInteract(Player $player, Item $item): bool
    {
        $arena = $this->getArena();
        if (($arena->isWaiting() || $arena->isStarting()) && $item->equals(Items::getScenarios())) {
            UHCForms::sendScenarios($player, $arena);
            return true;
        }

        return parent::onItemInteract($player, $item);
    }

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();

        if ($this->getArena()->isSpectator($player)) {
            $event->setRecipients($this->getArena()->getSpectators());
            $event->setMessage('§7Dead Chat > ' . $event->getMessage());
            $this->dispatchScopedChat($event);
        } elseif (!$this->getArena()->isSoloGame()) {
            /** @var UHCArena $arena */
            $arena = $this->getArena();
            $team = $arena->getTeam($player);

            if ($this->getArena()->isRunning()) {
                if (str_starts_with(TextFormat::clean($event->getMessage()), '!')) {
                    $event->setMessage(preg_replace('/!/', '', $event->getMessage(), 1) ?? "");
                } else {
                    $event->setRecipients($team->getAlivePlayers());
                    $event->setMessage($team->getColor() . 'Team > ' . $event->getMessage());
                    $this->dispatchScopedChat($event);
                }
            }
        }
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $pos = $event->getBlock()->getPosition();
        $x = $pos->getFloorX() >> 4;
        $z = $pos->getFloorZ() >> 4;
        $world = $pos->getWorld();
        if (!$world->isChunkLoaded($x, $z) || $world->isChunkLocked($x, $z)) {
            $event->cancel();
        }
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        $pos = $event->getBlock()->getPosition();
        $x = $pos->getFloorX() >> 4;
        $z = $pos->getFloorZ() >> 4;
        $world = $pos->getWorld();
        if (!$world->isChunkLoaded($x, $z) || $world->isChunkLocked($x, $z)) {
            $event->cancel();
        }
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $pos = $event->getBlock()->getPosition();
        $x = $pos->getFloorX() >> 4;
        $z = $pos->getFloorZ() >> 4;
        $world = $pos->getWorld();
        if (!$world->isChunkLoaded($x, $z) || $world->isChunkLocked($x, $z)) {
            $event->cancel();
        }
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $pos = $event->getBlock()->getPosition();
        $x = $pos->getFloorX() >> 4;
        $z = $pos->getFloorZ() >> 4;
        $world = $pos->getWorld();
        if (!$world->isChunkLoaded($x, $z) || $world->isChunkLocked($x, $z)) {
            $event->cancel();
        }
    }

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        $pos = $event->getBlock()->getPosition();
        $x = $pos->getFloorX() >> 4;
        $z = $pos->getFloorZ() >> 4;
        $world = $pos->getWorld();
        if (!$world->isChunkLoaded($x, $z) || $world->isChunkLocked($x, $z)) {
            $event->cancel();
        }
    }
}
