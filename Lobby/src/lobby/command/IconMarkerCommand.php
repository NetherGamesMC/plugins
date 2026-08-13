<?php
declare(strict_types=1);

namespace lobby\command;

use lobby\entity\custom\IconMarker;
use lobby\features\secret\SecretTokens;
use lobby\Lobby;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\commands\BaseCommand;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\permissions\Permissions;
use pocketmine\command\CommandSender;

class IconMarkerCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct("marker", NGEssentials::getInstance());

        $this->setPermission(Permissions::RANK_OWNER);
        $this->setPermissionMessage("command.reserved.estaff");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $player */
        $player = $sender;
        switch ($args[0]) {
            case "NPC":
                /*$zombie = new HumanNPC($player->getLocation());

                $zombie->spawnToAll();
                NPCUtility::attachDialogue($zombie, "Hello", "My name is someone", [new Button("Hello button!!!!", function (Player $player) use ($zombie) {
                    $player->sendMessage("Hello!");
                })]);
                break;*/
            case "marker":
                $entity = new IconMarker($player->getLocation());

                $entity->spawnToAll();
                $entity->setScale(floatval($args[1]));

                $entity->despawnFrom($player);

                $sender->sendMessage("Spawned!");
                break;
            case "animation":
                Lobby::getInstance()->getFeaturesManager()->getTokens()->playPickupAnimation($player, $args[1]);
                break;

            case "soundedit":
                SecretTokens::$SOUND_NAME = $args[1];
                SecretTokens::$SOUND_PITCH = (int)$args[2];
                break;
            case "fly":
                $player->setAllowFlight(true);
                $player->setFlying(true);
                $player->sendMessage("Flight enabled");
                break;
            case "sound":
                PlayerUtils::playSound($player, $args[1], (int)$args[2]);

                break;
            case "pageination":
                /*$entity = new Zombie($player->getLocation());
                $entity->spawnToAll();

                $entity->setNameTag("test");
                $form = new DialogForm("content1");

                foreach ($buttons as $button) {
                    $form->addButton($button);
                }

                $form->setCloseListener(function (Player $player) use ($entity, $exitClosure) {
                    $exitClosure($player);
                    self::closeDialogue($player, $entity);
                });

                $form->pairWithEntity($entity);
                return $form;*/
        }


    }
}