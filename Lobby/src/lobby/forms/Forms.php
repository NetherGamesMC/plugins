<?php
declare(strict_types=1);

namespace lobby\forms;

use libforms\elements\Button;
use libforms\FormManager;
use lobby\features\zone\types\AutoDiscoveredZone;
use lobby\features\zone\types\BasicDiscoverableZone;
use lobby\utils\BaseTrait;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Forms
{
    use BaseTrait;

    public static function sendZoneMenu(Player $player): void
    {
        if (($form = FormManager::createSimpleForm($player)) === null) {
            return;
        }

        $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Zones");
        $form->setContent("While exploring the lobby you can discover a number of different areas. Once discovered, you can teleport to them again here.");

        $playerData = NGEssentials::getInstance()->getPlayerData();
        $discovered = $playerData->getArray($player, PlayerData::LOBBY_DISCOVERED_ZONES);

        $zones = [];
        foreach (NGEssentials::getInstance()->getPlayerManager()->getWorldFeatures()->getZonesManager()->getZones() as $zone) {
            if ($zone instanceof BasicDiscoverableZone) {
                $zones[] = $zone->getName();

                if ($zone instanceof AutoDiscoveredZone || in_array($zone->getName(), $discovered)) {
                    $form->addButton(new Button($zone->getName(), static function (Player $player) use ($zone): void {
                        $player->teleport($zone->getTeleportLocation());
                        $player->sendMessage("§aTeleported to " . $zone->getName());
                    }));
                }
            }
        }

        if ($player->hasPermission(Permissions::RANK_TESTER)) {
            if (count($zones) > count($discovered)) {
                $form->addButton(new Button(TextFormat::RED . "Unlock all", static function (Player $player) use ($zones, $playerData): void {
                    $playerData->setValue($player, PlayerData::LOBBY_DISCOVERED_ZONES, $zones);
                    $player->sendMessage("§l§cDEV§r: Unlocked all zones for testing.");
                }));
            } else {
                $form->addButton(new Button(TextFormat::RED . "Lock all", static function (Player $player) use ($playerData): void {
                    $playerData->setValue($player, PlayerData::LOBBY_DISCOVERED_ZONES, []);
                    $player->sendMessage("§l§cDEV§r: Locked all zones for testing.");
                }));
            }
        }

        $player->sendForm($form);
    }
}