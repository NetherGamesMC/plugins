<?php
declare(strict_types=1);

namespace lobby\entity\custom;

use lobby\entity\minecraft\NPC;
use lobby\entity\minecraft\NPCDeserializer;
use lobby\entity\minecraft\registry\ConditionRegistry;
use lobby\utils\npc\Button;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class ConditionalContentNPC extends NPC
{
    public function __construct(private string $idx, private string $conditionArgument, private array $conditionMapping, string $title, Location $location, Skin $skin, ?CompoundTag $nbt = null, ?string $openingSound = "beacon.power", ?int $openingPitch = 1)
    {
        parent::__construct(title: $title, location: $location, skin: $skin, buttons: [], nbt: $nbt, openingSound: $openingSound, openingPitch: $openingPitch);
    }

    public function resolveContent(Player $player): array
    {
        return $this->evaluateCondition($this->idx, $this->conditionArgument, $this->conditionMapping, $player);
    }

    private function evaluateCondition(string $idx, string $conditionArg, array $conditionMap, Player $player): array
    {
        $result = ConditionRegistry::getConditionalResult($idx, $conditionArg, $player);

        if (array_key_exists($result, $conditionMap)) {
            $resultMap = $conditionMap[$result];

            if (array_key_exists("condition", $resultMap)) {
                $nextCondition = $resultMap["condition"];
                $nextConditionIdx = $nextCondition["name"];
                $nextConditionArg = $nextCondition["arg"];
                $nextConditionResult = $nextCondition["results"];

                return $this->evaluateCondition($nextConditionIdx, $nextConditionArg, $nextConditionResult, $player);
            }

            $buttons = [];
            if (array_key_exists("buttons", $resultMap)) {
                foreach ($resultMap["buttons"] as $button) {
                    $buttons[] = new Button($button["text"], NPCDeserializer::resolveAction($button["action"], $button["arg"]), $button["arg"]);
                }
            }


            return [$resultMap["text"], $buttons];
        }

        return ["No dialogue", []];


    }

    public
    function getPickerOffset(): int
    {
        return -50;
    }
}