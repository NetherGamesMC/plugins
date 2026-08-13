<?php
declare(strict_types=1);

namespace lobby\features\npc;

use Closure;
use lobby\utils\npc\DialogForm;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\player\Player;

class NPCUtility
{
    public static function attachDialogue(Entity $entity, string $title, string $content, array $buttons = [], ?Closure $exitClosure = null): void
    {
        $entity->setNameTag($title);
        $form = new DialogForm($content);

        foreach ($buttons as $button) {
            $form->addButton($button);
        }

        $form->setCloseListener(function (Player $player) use ($entity, $exitClosure) {
            $exitClosure($player);
            self::closeDialogue($player, $entity);
        });

        $form->pairWithEntity($entity);
    }

    public static function closeDialogue(Player $player, Entity $entity): void
    {
        $player->getNetworkSession()->sendDataPacket(NpcDialoguePacket::create($entity->getId(), NpcDialoguePacket::ACTION_CLOSE, "", "", "", ""));
    }
}