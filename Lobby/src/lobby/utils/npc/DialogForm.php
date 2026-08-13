<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace lobby\utils\npc;

use Closure;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\form\FormValidationException;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class DialogForm
{

    /** @var string */
    private $dialogText;

    /** @var Button[] */
    private $buttons = [];

    /** @var Entity|null */
    private $entity = null;

    /** @var Closure|null */
    private $closeListener = null;

    private int $pickerOffset = 0;

    private ?Closure $optionalCallback = null;

    public function __construct(string $dialogText)
    {
        $this->dialogText = $dialogText;
        DialogFormStore::registerForm($this);

        $this->onCreation();
    }

    protected function onCreation(): void
    {
    }

    public function getDialogText(): string
    {
        return $this->dialogText;
    }

    public function setDialogText(string $dialogText): void
    {
        $this->dialogText = $dialogText;

        if ($this->entity !== null) {
            $this->entity->getNetworkProperties()->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogText);
        }
    }

    /**
     * @param Closure|null $optionalCallback
     */
    public function setOptionalCallback(?Closure $optionalCallback): void
    {
        $this->optionalCallback = $optionalCallback;
    }

    public function addButton(Button $button): void
    {
        $this->buttons[] = $button;
    }

    public function getEntity(): ?Entity
    {
        return $this->entity;
    }

    public function getCloseListener(): ?Closure
    {
        return $this->closeListener;
    }

    public function setCloseListener(?Closure $closeListener): void
    {
        if ($closeListener !== null) {
            Utils::validateCallableSignature(function (Player $player) {
            }, $closeListener);
        }
        $this->closeListener = $closeListener;
    }

    /**
     * @return int
     */
    public function getPickerOffset(): int
    {
        return $this->pickerOffset;
    }

    /**
     * @param int $pickerOffset
     */
    public function setPickerOffset(int $pickerOffset): void
    {
        $this->pickerOffset = $pickerOffset;
    }

    public function pairWithEntity(Entity $entity): void
    {
        if ($entity instanceof Player) {
            throw new InvalidArgumentException("NpcForms can't be paired with players.");
        }

        if ($this->entity !== null) {
            $this->entity->getNetworkProperties()->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 0);
        }

        if (($otherForm = DialogFormStore::getFormByEntity($entity)) !== null) {
            DialogFormStore::unregisterForm($otherForm);
        }

        $this->entity = $entity;

        $propertyManager = $entity->getNetworkProperties();
        $propertyManager->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
        $propertyManager->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogText);
        $propertyManager->setString(EntityMetadataProperties::NPC_ACTIONS, json_encode($this->buttons));
        $propertyManager->setString(EntityMetadataProperties::NPC_SKIN_INDEX, '{"picker_offsets":{"scale":[0,0,0],"translate":[0,0,0]},"portrait_offsets":{"scale":[1,1,1],"translate":[0,' . $this->pickerOffset . ',0]}}');
    }

    public function handleResponse(Player $player, mixed $response): void
    {

        if ($response === null) {
            $this->executeCloseListener($player);
        } elseif (is_int($response) and array_key_exists($response, $this->buttons)) {
            if ($this->optionalCallback !== null) {
                ($this->optionalCallback)($player, $response);
                $this->executeCloseListener($player);

                return;
            }
            $this->buttons[$response]->executeSubmitListener($player);
        } elseif (is_int($response)) {
            if ($this->optionalCallback !== null) {
                ($this->optionalCallback)($player, $response);
                $this->executeCloseListener($player);
            }
        } else {
            throw new FormValidationException("Couldn't validate DialogForm with response $response");
        }
    }

    public function executeCloseListener(Player $player): void
    {
        if ($this->closeListener !== null) {
            ($this->closeListener)($player);
        }
    }

}