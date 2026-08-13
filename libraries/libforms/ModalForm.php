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
use InvalidArgumentException;
use libforms\elements\Button;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use function array_replace;
use function is_bool;

class ModalForm extends Form
{
    /** @var Button[] */
    private array $buttons = [];
    /** @var string */
    private string $content;
    /** @phpstan-var Closure(Player): void */
    private ?Closure $onClose;

    public function __construct(?Player $player, ?Closure $onClose = null)
    {
        $this->onClose = $onClose;

        parent::__construct($player);
    }

    public function handleResponse(Player $player, $data): void
    {
        if (is_bool($data)) {
            if ($data) {
                $button = $this->getButton1();
            } else {
                $button = $this->getButton2();
            }

            $button?->runCallable($player);
        } elseif ($data === null) {
            if (($callable = $this->getCloseClosure()) !== null) {
                $callable($player);
            }
        } elseif (!empty($data)) {
            throw new FormValidationException();
        }
    }

    public function getButton1(): ?Button
    {
        return $this->getButton(1);
    }

    private function getButton(int $buttonId): ?Button
    {
        return $this->buttons[$buttonId] ?? null;
    }

    public function getButton2(): ?Button
    {
        return $this->getButton(2);
    }

    public function getCloseClosure(): ?Closure
    {
        return $this->onClose;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setButton1(Button $button): void
    {
        $this->setButton(1, $button);
    }

    private function setButton(int $buttonId, Button $button): void
    {
        if ($buttonId !== 1 && $buttonId !== 2) {
            throw new InvalidArgumentException("Button ID must be between 1 and 2");
        }

        $this->buttons[$buttonId] = $button;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        return array_replace($data, [
            'type' => 'modal',
            'content' => $this->getContent(),
            'button1' => $this->getButton1()?->getData($this->getPlayer())['text'] ?? '',
            'button2' => $this->getButton2()?->getData($this->getPlayer())['text'] ?? ''
        ]);
    }

    /**
     * @return Button[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function setButton2(Button $button): void
    {
        $this->setButton(2, $button);
    }

    public function setCloseClosure(?Closure $onClose): void
    {
        $this->onClose = $onClose;
    }
}
