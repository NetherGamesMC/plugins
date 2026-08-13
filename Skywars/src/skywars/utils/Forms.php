<?php
/**
 *           ____    _             __        __
 *  __  __ / ___|  | | __  _   _  \ \      / /   __ _   _ __   ___
 *  \ \/ / \___ \  | |/ / | | | |  \ \ /\ / /   / _` | | '__| / __|
 *   >  <   ___) | |   <  | |_| |   \ V  V /   | (_| | | |    \__ \
 *  /_/\_\ |____/  |_|\_\  \__, |    \_/\_/     \__,_| |_|    |___/
 *                         |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker
 *
 */
declare(strict_types=1);

namespace skywars\utils;

use libforms\elements\Button;
use libforms\FormManager;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use skywars\kits\Kit;
use skywars\Skywars;
use skywars\SWArena;

abstract class Forms extends \libminigames\utils\Forms
{
    public static function sendFreeKitSelectionForm(Player $player, SWArena $arena): void
    {
        $form = FormManager::createSimpleForm($player);

        if ($form === null) {
            return;
        }

        $form->setTitle("Kit Selection");
        $form->setContent("Select a kit:");

        $settings = $arena->getGameSettings();
        $current = $settings->getKit($player->getId());
        $type = $arena->getType();

        /**
         * @var Skywars $minigame
         */
        $minigame = Skywars::getInstance();

        /**
         * @var Kit $kit
         */
        foreach ($minigame->getKitManager()->getType($type) as $kit) {
            $form->addButton(new Button(($kit->getName() === $current?->getName() ? TextFormat::YELLOW : TextFormat::GREEN) . $kit->getName(), function () use ($settings, $player, $kit) {
                if (!$player->isConnected()) return;
                $settings->setKit($player->getId(), $kit);
                Translator::sendMessage($player, "skywars.kit.selected", Translator::TYPE_INFO, ...["kit" => $kit->getName()]);
            }));
        }

        $form->addButton(new Button(($current === null ? TextFormat::YELLOW : TextFormat::GREEN) . "No Kit", static fn() => $settings->setKit($player->getId(), null)));
        $form->sendForm();
    }
}
