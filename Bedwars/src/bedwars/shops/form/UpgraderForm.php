<?php
/**
 *         _____            _
 *        | ___ \          | |
 *  __  __| |_/ /  ___   __| |__      __  __ _  _ __  ___
 *  \ \/ /| ___ \ / _ \ / _` |\ \ /\ / / / _` || '__|/ __|
 *   >  < | |_/ /|  __/| (_| | \ V  V / | (_| || |   \__ \
 *  /_/\_\\____/  \___| \__,_|  \_/\_/   \__,_||_|   |___/
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

namespace bedwars\shops\form;

use bedwars\BWTeam;
use bedwars\shops\Trap;
use bedwars\shops\Upgrade;
use bedwars\shops\Upgrader;
use bedwars\utils\TrapManager;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\FormManager;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use function count;

final class UpgraderForm
{
    public function __construct(private readonly Upgrader $upgrader)
    {
    }

    public function sendTrapQueue(Player $player, BWTeam $team, string $content = ""): void
    {
        if (($form = FormManager::createSimpleForm($player)) === null) {
            return;
        }

        $traps = $team->getTrapManager()->getTraps();
        $cost = $team->getTrapManager()->getNextTrapCost();
        $item = VanillaItems::DIAMOND()->setCount($cost);
        $form->setTitle("Queue a trap");
        // If there is no text given, use the default text
        if ($content === "") {
            $queue = [];
            for ($slot = 0; $slot < TrapManager::MAX_QUEUED_TRAPS; $slot++) {
                $displayed = $slot + 1;
                $name = isset($traps[$slot]) ? $traps[$slot]->name : "Empty";
                $queue[$slot] = "Trap #$displayed - $name";
            }
            $content = implode(TextFormat::EOL, $queue);
        }
        $form->setContent($content);

        $color = $player->getInventory()->contains($item) ? TextFormat::GREEN : TextFormat::RED;
        foreach (Trap::getAll() as $trap) {
            $form->addButton(new ImageButton(
                text: $color . $trap->name . TextFormat::EOL . TextFormat::GRAY . "Cost: " . TextFormat::AQUA . $cost . " Diamond" . ($cost === 1 ? '' : 's'),
                imageType: ImageButton::IMAGE_TYPE_PATH,
                imageSource: $trap->iconUrl,
                callable: function (Player $player) use ($team, $item, $trap) {
                    $traps = $team->getTrapManager()->getTraps();
                    if (($trapsCount = count($traps)) >= TrapManager::MAX_QUEUED_TRAPS) {
                        $this->sendTrapQueue($player, $team, TextFormat::RED . "Failed!" . TextFormat::RESET . " Trap queue full");
                        return;
                    }

                    if (($form = FormManager::createModalForm($player)) !== null) {
                        $form->setContent($trap->description);
                        $form->setButton1($player->getInventory()->contains($item) ?
                            new Button(
                                text: TextFormat::GREEN . "Purchase",
                                callable: function (Player $player) use ($team, $item, $trap, $trapsCount) {
                                    // If queue is successful, don't send player back to the trap queue form
                                    if ($this->getUpgrader()->queue($player, $team, $trap, ($trapsCount + 1))) {
                                        $player->getInventory()->removeItem($item);
                                        return;
                                    }
                                    $this->sendTrapQueue($player, $team);
                                }
                            ) :
                            new Button(
                                text: TextFormat::RED . "Not enough diamonds",
                                callable: function (Player $player) use ($team): void {
                                    $this->sendTrapQueue($player, $team, TextFormat::RED . "Failed!" . TextFormat::RESET . " Not enough diamonds");
                                }
                            )
                        );
                        $form->setButton2(new Button(
                            text: TextFormat::RED . "Back",
                            callable: function (Player $player) use ($team): void {
                                $this->sendTrapQueue($player, $team);
                            }
                        ));
                        $form->sendForm();
                    }
                }
            ));
        }
        $form->addButton(new ImageButton(
            text: TextFormat::RED . TextFormat::BOLD . "Exit",
            imageType: ImageButton::IMAGE_TYPE_PATH,
            imageSource: "textures/blocks/barrier",
            callable: function (Player $player) use ($team): void {
                $this->send($player, $team);
            }
        ));

        $form->sendForm();
    }

    public function getUpgrader(): Upgrader
    {
        return $this->upgrader;
    }

    public function send(Player $player, BWTeam $team, string $text = ""): void
    {
        if (($form = FormManager::createSimpleForm($player)) === null) {
            return;
        }
        $form->setTitle("Upgrades & Traps");
        $form->setContent($text);
        $buttons = [
            new Button(text: TextFormat::YELLOW . "Buy a trap", callable: function (Player $player) use ($team): void {
                $this->sendTrapQueue($player, $team);
            }),
            ...array_map(
                callback: function (Upgrade $upgrade) use ($team, $player): Button {
                    $currentTierLevel = $team->getUpgradeLevel($upgrade);
                    // If the upgrade is not available, show it as fully upgraded
                    if (!$upgrade->hasUpgrade($currentTierLevel)) {
                        // Name consists of the upgrade name and the tier name as a roman numeral (e.g. "Reinforced Armor II")
                        return new ImageButton(
                            text: TextFormat::RED . $upgrade->getFormattedName($currentTierLevel) . TextFormat::EOL . "Fully upgraded",
                            imageType: ImageButton::IMAGE_TYPE_PATH,
                            imageSource: $upgrade->iconUrl,
                            callable: function (Player $player) use ($team): void {
                                $this->send($player, $team);
                            }
                        );
                    }

                    $nextTierLevel = $currentTierLevel + 1;
                    $nextTier = $upgrade->getTier($nextTierLevel) ?? throw new AssumptionFailedError("Upgrade tier not found");
                    $cost = $team->getArena()->isTriosOrSquads() ? $nextTier->teamCost : $nextTier->cost;
                    // This item is used to check if the player has enough diamonds to purchase the upgrade
                    $costItem = VanillaItems::DIAMOND()->setCount($cost);
                    // If the player has enough diamonds, the button color is green. Otherwise, it is red.
                    $color = $player->getInventory()->contains($costItem) ? TextFormat::GREEN : TextFormat::RED;

                    return new ImageButton(
                        text: $color . $upgrade->getFormattedName($nextTierLevel) . TextFormat::EOL . TextFormat::GRAY . "Cost: " . TextFormat::AQUA . $cost . " Diamond" . ($cost === 1 ? "" : "s"),
                        imageType: ImageButton::IMAGE_TYPE_PATH,
                        imageSource: $upgrade->iconUrl,
                        callable: function (Player $player) use ($team, $upgrade, $nextTier, $nextTierLevel, $costItem): void {
                            $form = FormManager::createModalForm($player);
                            if ($form === null) {
                                return;
                            }
                            $form->setContent($nextTier->description);
                            $form->setButton1($player->getInventory()->contains($costItem) ?
                                new Button(
                                    text: TextFormat::GREEN . "Purchase",
                                    callable: function (Player $player) use ($team, $costItem, $upgrade, $nextTierLevel): void {
                                        if ($this->getUpgrader()->upgrade($player, $team, $upgrade, $nextTierLevel)) {
                                            $player->getInventory()->removeItem($costItem);
                                        }
                                    }
                                ) :
                                new Button(
                                    text: TextFormat::RED . "Not enough diamonds",
                                    callable: function (Player $player) use ($team): void {
                                        $this->send($player, $team, TextFormat::RED . "Failed!" . TextFormat::RESET . " Not enough diamonds");
                                    }
                                )
                            );
                            $form->setButton2(new Button(TextFormat::RED . 'Back', function (Player $player) use ($team) {
                                $this->send($player, $team);
                            }));

                            $form->sendForm();
                        }
                    );
                },
                array: Upgrade::getAll()
            ),
            new ImageButton(text: TextFormat::RED . TextFormat::BOLD . "Exit", imageType: ImageButton::IMAGE_TYPE_PATH, imageSource: "textures/blocks/barrier")
        ];

        // We really should be using the new libforms branch, but this works for now.
        foreach ($buttons as $button) {
            $form->addButton($button);
        }
        $form->sendForm();
    }
}