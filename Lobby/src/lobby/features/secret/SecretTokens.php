<?php
declare(strict_types=1);

namespace lobby\features\secret;

use libPhysX\PhysX;
use lobby\entity\custom\IconMarker;
use lobby\features\npc\NPCUtility;
use lobby\Lobby;
use lobby\utils\BaseTrait;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\entity\custom\FloatingText;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class SecretTokens
{
    use BaseTrait;

    private const PICKUP_ANIMATION_ANCHOR = [20.5, 26.8, 35.5];
    private const LOCKED = "locked";
    private const UNLOCKED = "unlocked";
    public static int $SOUND_PITCH = 1;
    public static string $SOUND_NAME = "random.toast";
    private array $entities = [];

    public function prepareEntities(World $world): void
    {
        foreach (SecretData::SECRET_STANDS as $secret) {
            ["stand" => $stand, "name" => $name] = $secret;

            $standCoordinate = $this->parseCoordinate($stand);
            $unlockedEntity = new IconMarker(Location::fromObject($standCoordinate, $world));
            $unlockedEntity->setNameTag(TextFormat::GREEN . $name);

            $lockedEntity = new FloatingText(Location::fromObject($standCoordinate, $world), "§cNot discovered ($name)");
            $this->entities[$name] = [self::LOCKED => $lockedEntity, self::UNLOCKED => $unlockedEntity];
        }
    }

    private function parseCoordinate(array $coordinate): Vector3
    {
        return new Vector3($coordinate["x"], $coordinate["y"], $coordinate["z"]);
    }

    public function spawnSecrets(Player $player): void
    {
        $plugin = NGEssentials::getInstance();
        $collected = $plugin->getPlayerData()->getArray($player, PlayerData::LOBBY_COLLECTED_TOKENS);
        if (count($collected) === count(SecretData::SECRET_STANDS)) {
            // person unlocked all secrets
            $elytra = Lobby::getInstance()->getEntityManager()->getElytraItem();
            $elytra->addPlayer($player);
            $elytra->spawnTo($player);

            $cosmetic = CosmeticHandler::CHESTPLATES();
            $cosmetic->give($player, $cosmetic->getEntry(101)); // Elytra
        }

        foreach (SecretData::SECRET_STANDS as $secret) {
            ["name" => $name] = $secret;

            if (in_array($name, $collected, true)) {
                /** @var IconMarker $entity */
                $entity = $this->entities[$name][self::UNLOCKED];
                $entity->addTo($player);
                $entity->spawnTo($player);
            } else {
                /** @var FloatingText $float */
                $float = $this->entities[$name][self::LOCKED];
                $float->spawn($player);
            }
        }
    }

    public function playPickupAnimation(Player $player, string $secretIdentifier): void
    {
        $previousPosition = $player->getLocation();

        $player->setNoClientPredictions(true);
        [$x, $y, $z] = self::PICKUP_ANIMATION_ANCHOR;
        $coordinate = $this->parseCoordinate(SecretData::SECRET_STANDS[$secretIdentifier]["stand"]);

        $vector = new Vector3($x, $y, $z);
        $rotation = PhysX::calculateRotationEulerAngle($vector, $coordinate);
        $player->teleport(Location::fromObject($vector, $player->getWorld(), $rotation->yaw, $rotation->pitch));

        /** @var IconMarker $unlockEntity */
        $unlockEntity = $this->entities[$secretIdentifier][self::UNLOCKED];

        $unlockEntity->addTo($player);
        $unlockEntity->spawnTo($player);
        $player->setInvisible();

        $ess = NGEssentials::getInstance();
        $playerData = $ess->getPlayerData();
        $playerManager = $ess->getPlayerManager();

        $array = $playerData->getArray($player, PlayerData::LOBBY_COLLECTED_TOKENS);
        if (!in_array($secretIdentifier, $array, true)) {
            $array[] = $secretIdentifier;
        }

        $playerData->setValue($player, PlayerData::LOBBY_COLLECTED_TOKENS, $array);
        if (count(SecretData::SECRET_STANDS) === count($array)) {
            Translator::sendMessage($player, "secret.unlock.all", Translator::TYPE_SUCCESS);

            $cosmetic = CosmeticHandler::CHESTPLATES();
            $cosmetic->give($player, $cosmetic->getEntry(101)); // Elytra

            PlayerUtils::playSound($player, "mob.enderdragon.growl", 1);

            $elytra = Lobby::getInstance()->getEntityManager()->getElytraItem();
            $elytra->addPlayer($player);
            $elytra->spawnTo($player);
        }

        NPCUtility::attachDialogue($unlockEntity, "§a" . $secretIdentifier, "Congratulations, you've successfully collected one of the secret trophies... \n\nA real adventurer are you, hmm? Let me just tell you, there's a lot to explore!", [], function (Player $player) use ($previousPosition, $secretIdentifier) {
            Lobby::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $previousPosition, $secretIdentifier): void {
                if ($secretIdentifier == SecretData::MAZE) {
                    $player->teleport($player->getWorld()->getSafeSpawn());
                } else {
                    $player->teleport($previousPosition);
                }

                $player->setNoClientPredictions(false);
                $player->setInvisible(false);
            }), 20 * 3);
        });

        // The forms need a bit of delay eventually
        Lobby::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $unlockEntity): void {
            PlayerUtils::playSound($player, self::$SOUND_NAME, self::$SOUND_PITCH);
            $dialogue = NpcDialoguePacket::create($unlockEntity->getId(), NpcDialoguePacket::ACTION_OPEN, '', "", "test2", '');
            $player->getNetworkSession()->sendDataPacket($dialogue);
        }), 20);


        /** @var FloatingText $lockEntity */
        $lockEntity = $this->entities[$secretIdentifier][self::LOCKED];
        $lockEntity->despawn($player);
    }
}