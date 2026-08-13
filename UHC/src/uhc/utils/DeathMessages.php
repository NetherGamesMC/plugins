<?php
declare(strict_types=1);

namespace uhc\utils;

use NetherGames\NGEssentials\entity\Arrow;
use NetherGames\NGEssentials\entity\Snowball;
use pocketmine\block\BlockTypeIds;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function mt_rand;

// A class that not even the creator of it loves
final class DeathMessages
{

    public static function selectDeathMessage(EntityDamageEvent $event, Player $victim): string
    {
        switch ($event->getCause()) {
            case EntityDamageEvent::CAUSE_CONTACT:
                if ($event instanceof EntityDamageByBlockEvent) {
                    return $victim->getDisplayName() . TextFormat::GRAY . " thought the " . TextFormat::AQUA .
                        $event->getDamager()->getName() . TextFormat::GRAY . " wouldn't hurt them";
                }
                break;
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    if ($damager === null) {
                        return self::getGenericMessage($victim);
                    }

                    return self::getEntityAttackMessage($victim, $damager);
                }
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                if ($event instanceof EntityDamageByChildEntityEvent) {
                    $damager = $event->getDamager();
                    if ($damager === null) {
                        return self::getGenericMessage($victim);
                    }

                    return self::getProjectileMessage($victim, $damager, $event->getChild());
                }
                break;
            case EntityDamageEvent::CAUSE_SUFFOCATION:
                return $victim->getDisplayName() . TextFormat::GRAY . " tried to breathe in a block";
            case EntityDamageEvent::CAUSE_FALL:
                if (mt_rand(0, 1) === 1) {
                    $distance = floor($victim->fallDistance);
                    return $victim->getDisplayName() . TextFormat::GRAY . " broke their legs after falling $distance blocks";
                } else {
                    return $victim->getDisplayName() . TextFormat::GRAY . " wasn't very good at skydiving";
                }
            case EntityDamageEvent::CAUSE_FIRE:
            case EntityDamageEvent::CAUSE_FIRE_TICK:
                return $victim->getDisplayName() . TextFormat::GRAY . " had a turn on the grill";
            case EntityDamageEvent::CAUSE_LAVA:
                return $victim->getDisplayName() . TextFormat::GRAY . " tried to swim in lava";
            case EntityDamageEvent::CAUSE_DROWNING:
                return $victim->getDisplayName() . TextFormat::GRAY . " wasn't very good at swimming";
            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
                return $victim->getDisplayName() . TextFormat::GRAY . " cut the wrong wire";
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                return $victim->getDisplayName() . TextFormat::GRAY . " got creeped up on";
            case EntityDamageEvent::CAUSE_MAGIC:
                return self::getMagicMessage($victim, $event);
            case EntityDamageEvent::CAUSE_CUSTOM:
                return $victim->getDisplayName() . TextFormat::GRAY . " didn't make it in time";
            case EntityDamageEvent::CAUSE_STARVATION:
                return $victim->getDisplayName() . TextFormat::GRAY . " won't be hungry anymore";
        }

        return self::getGenericMessage($victim);
    }

    private static function getGenericMessage(Player $victim): string
    {
        return TextFormat::GRAY . "We won't be seeing {$victim->getDisplayName()}" . TextFormat::GRAY . " again";
    }

    private static function getEntityAttackMessage(Player $victim, Entity $damager): string
    {
        $item = $damager instanceof Player ? $damager->getInventory()->getItemInHand() : VanillaItems::AIR();
        $name = $damager instanceof Player ? $damager->getDisplayName() : $damager->getNameTag();

        $message = $victim->getDisplayName() . TextFormat::GRAY . " was slain by $name";
        if ($item->getTypeId() === BlockTypeIds::AIR) {
            return $message;
        }

        $itemColor = TextFormat::GRAY;
        if ($item instanceof TieredTool) {
            switch ($item->getTier()) {
                case ToolTier::WOOD:
                    return TextFormat::GOLD;
                case ToolTier::STONE:
                    return TextFormat::DARK_GRAY;
                case ToolTier::IRON:
                    return TextFormat::WHITE;
                case ToolTier::GOLD:
                    return TextFormat::ESCAPE . "g"; //TODO: Remove this when PM supports it
                case ToolTier::DIAMOND:
                    return TextFormat::AQUA;
            }
        }

        $itemName = $item->hasCustomName() ? $item->getCustomName() : $item->getVanillaName();
        $message .= TextFormat::GRAY . " using$itemColor $itemName";
        return $message;
    }

    private static function getProjectileMessage(Player $victim, Entity $damager, ?Entity $child): string
    {
        $name = $damager instanceof Player ? $damager->getDisplayName() : $damager->getNameTag();
        if ($child instanceof Snowball) {
            return $victim->getDisplayName() . TextFormat::GRAY . " lost a snowball fight against $name";
        } elseif ($child instanceof Arrow) {
            return $name . TextFormat::GRAY . " was better at archery than {$victim->getDisplayName()}";
        }

        return self::getGenericMessage($victim);
    }

    private static function getMagicMessage(Player $victim, EntityDamageEvent $event): string
    {
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $damager = $event->getDamager();
            if ($damager === null) {
                return self::getGenericMessage($victim);
            }

            $name = $damager instanceof Player ? $damager->getDisplayName() : $damager->getNameTag();
            return "$name's " . TextFormat::GRAY . " new brew proved too strong for {$victim->getDisplayName()}";
        }

        return $victim->getDisplayName() . TextFormat::GRAY . " played with potions. It didn't end well";
    }
}