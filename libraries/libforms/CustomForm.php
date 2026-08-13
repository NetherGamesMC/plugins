<?php
/**
 *   _ _ _      __
 *  | (_) |    / _|
 *  | |_| |__ | |_ ___  _ __ _ __ ___  ___
 *  | | | '_ \|  _/ _ \| '__| '_ ` _ \/ __|
 *  | | | |_) | || (_) | |  | | | | | \__ \
 *  |_|_|_.__/|_| \___/|_|  |_| |_| |_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace libforms;

use Closure;
use libforms\elements\Element;
use libforms\elements\Label;
use pocketmine\form\FormValidationException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\player\Player;
use function array_filter;
use function array_map;
use function array_replace;
use function is_array;

class CustomForm extends Form
{
    /** @var Element[] */
    private array $elements = [];
    /** @var Closure|null */
    private ?Closure $callable;
    /** @phpstan-var Closure(Player): void */
    private ?Closure $onClose;
    /** @phpstan-var Closure(Player): void */
    private ?Closure $onSubmit = null;

    public function __construct(?Player $player, ?Closure $callable = null, ?Closure $onClose = null)
    {
        $this->callable = $callable;
        $this->onClose = $onClose;

        parent::__construct($player);
    }

    public function handleResponse(Player $player, $data): void
    {
        $elements = $this->getElements();

        if ($player->getNetworkSession()->getProtocolId() === ProtocolInfo::PROTOCOL_1_21_70) {
            $elements = array_values(array_filter($elements, fn(Element $element) => !($element instanceof Label)));
        }

        if (is_array($data)) {
            $goBack = true;
            $sendData = false;

            foreach ($data as $index => $value) {
                if (isset($elements[$index])) {
                    /** @var Dropdown|Input|Label|Slider|StepSlider|Toggle $element */
                    $element = $elements[$index];

                    if ($element instanceof Label || ($value === $element->getDefault() && !$element->isCallbackOnDefault())) {
                        continue;
                    }

                    $goBack = false;

                    if (!$element->runCallable($player, $value)) {
                        $sendData = true;
                    }
                }
            }

            if (($callable = $this->getCallable()) !== null) {
                if ($goBack) {
                    $callable($player);
                } else {
                    if ($sendData) {
                        $callable($player, $data);
                    } elseif (($onSubmit = $this->getSubmitClosure()) !== null) {
                        $onSubmit($player);
                    }
                }
            }
        } elseif ($data === null) {
            if (($callable = $this->getCloseClosure()) !== null) {
                $callable($player);
            }
        } elseif (!empty($data)) {
            throw new FormValidationException();
        }
    }

    public function getSubmitClosure(): ?Closure
    {
        return $this->onSubmit;
    }

    public function setSubmitClosure(?Closure $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    /**
     * @return Element[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function getCallable(): ?Closure
    {
        return $this->callable;
    }

    public function setCallable(?Closure $callable): void
    {
        $this->callable = $callable;
    }

    public function getCloseClosure(): ?Closure
    {
        return $this->onClose;
    }

    public function setCloseClosure(?Closure $onClose): void
    {
        $this->onClose = $onClose;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        return array_replace($data, [
            'type' => 'custom_form',
            'content' => array_map(function (Element $element): array {
                return $element->getData($this->getPlayer());
            }, $this->getElements())
        ]);
    }

    public function addElement(Element $element): void
    {
        $this->elements[] = $element;
    }
}