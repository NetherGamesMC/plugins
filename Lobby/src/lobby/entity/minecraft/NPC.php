<?php
declare(strict_types=1);

namespace lobby\entity\minecraft;

use libPhysX\PhysX;
use lobby\entity\minecraft\registry\ButtonCallbackRegistry;
use lobby\features\npc\NPCUtility;
use lobby\Lobby;
use lobby\utils\npc\Button;
use lobby\utils\npc\DialogForm;
use lobby\utils\PlayerUtils;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\ChunkLoader;
use pocketmine\world\format\Chunk;

abstract class NPC extends Human implements ChunkLoader
{
    /** @var int */
    private int $movementTick = 0;

    public function __construct(private string  $title,
                                Location        $location,
                                Skin            $skin,
                                private array   $buttons = [],
                                ?CompoundTag    $nbt = null,
                                private ?string $openingSound = "beacon.power",
                                private ?int    $openingPitch = 1
    )
    {
        parent::__construct($location, $skin, $nbt);

        $chunkX = $location->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $location->getFloorZ() >> Chunk::COORD_BIT_SIZE;
        $location->getWorld()->registerChunkLoader($this, $chunkX, $chunkZ, true);

        if ($this->openingSound == null) {
            $this->openingSound = "beacon.power";
        }

        $this->openingPitch = $this->openingPitch ?? 1;
        $this->setNameTag($title);
        $form = new DialogForm("");
        $form->setOptionalCallback(function (Player $player, ?int $responseIndex) {
            $button = ButtonCallbackRegistry::getAction($player, $responseIndex);

            ($button->getSubmitListener())($player, $button->getArgs());
        });
        $form->setPickerOffset(-55);

        foreach ($buttons as $button) {
            $form->addButton($button);
        }

        $entity = $this;

        $form->setCloseListener(function (Player $player) use ($entity) {
            NPCUtility::closeDialogue($player, $entity);
        });

        $form->pairWithEntity($entity);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {

                [$phrase, $buttons] = $this->resolveContent($damager);
                $this->changeNPCContent($phrase, $buttons, [$damager]);

                Lobby::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($phrase, $damager): void {
                    if (!$damager->isConnected()) {
                        return;
                    }

                    $this->openTo($damager, $phrase);
                }), 5);
            }
        }
    }

    public abstract function resolveContent(Player $player): array;

    private function changeNPCContent(string $text, array $buttons, array $players): void
    {
        $registryIndexes = [];
        /** @var Button $button */
        foreach ($buttons as $index => $button) {
            $registryIndexes[$index] = $button;
        }

        /** @var Player $player */
        foreach ($players as $player) {
            ButtonCallbackRegistry::registerForPlayer($player, $registryIndexes);
        }

        $array = [
            EntityMetadataProperties::INTERACTIVE_TAG => new StringMetadataProperty($text),
            EntityMetadataProperties::NPC_ACTIONS => new StringMetadataProperty(json_encode($buttons)),
        ];

        NetworkBroadcastUtils::broadcastEntityEvent(
            $players,
            fn(EntityEventBroadcaster $broadcaster, array $recipients) => $broadcaster->syncActorData($recipients, $this, $array)
        );
    }

    public function openTo(Player $player, ?string $phrase = null): void
    {
        if ($phrase === null) {
            [$phrase, $buttons] = $this->resolveContent($player);
        }

        $dialogue = NpcDialoguePacket::create($this->getId(), NpcDialoguePacket::ACTION_OPEN, $phrase, "", $this->title, '');
        $player->getNetworkSession()->sendDataPacket($dialogue);

        if ($this->openingSound !== "") {
            PlayerUtils::playSound($player, $this->openingSound, $this->openingPitch);
        }
    }

    /**
     * @return array
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    abstract public function getPickerOffset(): int;

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->movementTick += $tickDiff;
        $world = $this->getWorld();
        $location = $this->getLocation();

        /** @var NGPlayer[] $players */
        $players = $world->getPlayers();
        $players = array_filter($players, static function (NGPlayer $player) {
            return !$player->isInvisible();
        });

        if ($this->movementTick >= 3) {
            $closestPlayer = null;
            $closestDistance = 0;
            foreach ($players as $player) {
                $playerDistance = $location->distanceSquared($player->getLocation());

                if ($playerDistance < 10 && ($closestDistance === 0 || $closestDistance >= $playerDistance)) {
                    $closestDistance = $playerDistance;
                    $closestPlayer = $player;
                }
            }

            if ($closestPlayer !== null) {
                $rotation = PhysX::calculateRotationEulerAngle($this->getOffsetPosition($location), $closestPlayer->getEyePos());
                if ($rotation->yaw === $location->getYaw()) {
                    $this->movementTick = 0;

                    return parent::entityBaseTick($tickDiff); // Do not send anything if the rotation is unchanged
                }
                $this->setRotation($rotation->yaw, 0);
            }

            $this->movementTick = 0;
        }

        return parent::entityBaseTick($tickDiff);
    }

}