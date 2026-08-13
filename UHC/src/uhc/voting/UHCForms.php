<?php
declare(strict_types=1);

namespace uhc\voting;

use libforms\elements\Label;
use libforms\elements\Toggle;
use libforms\FormManager;
use libminigames\utils\Forms;
use pocketmine\player\Player;
use uhc\game\scenario\base\ScenarioRegistry;
use uhc\game\UHCArena;

class UHCForms extends Forms
{

    public static function sendScenarios(Player $player, UHCArena $arena): void
    {
        $form = FormManager::createCustomForm($player, function (Player $player, ?array $data = null) use ($arena) {
            if ($data === null) return;
            $index = 0;
            $scenarioData = [];
            foreach ($data as $value) {
                if ($value === null) {
                    continue;
                }
                $scenarioData[$index] = $value;
                $index++;
            }

            $index = 0;
            $mappedScenarios = [];
            $votedScenarios = [];
            foreach (ScenarioRegistry::getAll() as $scenario) {
                if ($scenario->isAlwaysActive()) {
                    continue;
                }
                $mappedScenarios[$scenario->getName()] = $scenarioData[$index];
                $index++;
            }

            foreach ($mappedScenarios as $mappedScenario => $enabled) {
                if (!$enabled) {
                    continue;
                }
                $votedScenarios[] = $mappedScenario;
                $player->sendMessage("§aYou voted for §6" . ScenarioRegistry::fromString($mappedScenario)->getDisplayName());
            }

            $arena->addTypeVote($player, $votedScenarios);
        });

        if ($form !== null) {
            $form->setTitle("Scenarios");
            foreach (ScenarioRegistry::getAll() as $scenario) {
                if ($scenario->isAlwaysActive()) {
                    continue;
                }
                $form->addElement(new Toggle($scenario->getDisplayName(), in_array($scenario->getName(), $arena->getVotedScenariosFromPlayer($player))));
                $form->addElement(new Label($scenario->getDescription()));
            }
            $form->sendForm();
        }
    }
}
