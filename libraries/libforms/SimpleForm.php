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
use function array_map;
use function array_replace;
use function count;
use function is_int;

class SimpleForm extends Form
{
    public const FORM_NEWS = '§m§r';
    public const FORM_TABS = '§g§r';
    public const FORM_TABS_WITH_SKIN = '§g§r§r';
    public const FORM_VOTE = '§j§r';
    public const FORM_COLUMN_2 = '§z';
    public const FORM_ITEMS_2 = '§w';
    public const FORM_ITEMS_3 = '§y';
    public const FORM_ITEMS_4 = '§v';
    public const FORM_X3_X2 = '§x';

    public const BUTTON_X3_X2_TOP = '§t§r';
    public const BUTTON_X3_X2_BOTTOM = '§l§r';

    public const BUTTON_BACK = '§c§l§r';
    public const BUTTON_TAB = '§t§b§r';

    /** @var Button[] */
    private array $buttons = [];
    private string $content = '';
    private string $type = "";
    /** @phpstan-var Closure(Player): void */
    private ?Closure $onClose;
    private ?Closure $onBack = null;

    public function __construct(?Player $player, ?Closure $onClose = null)
    {
        $this->onClose = $onClose;

        parent::__construct($player);
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public static function getDynamicType(int $count): string
    {
        return match ($count) {
            2 => self::FORM_ITEMS_2,
            3 => self::FORM_ITEMS_3,
            4 => self::FORM_ITEMS_4,
            default => throw new InvalidArgumentException("Invalid button count $count, expected 2, 3 or 4")
        };
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function setBackClosure(?Closure $onBack): void
    {
        $this->onBack = $onBack;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function handleResponse(Player $player, $data): void
    {
        $buttons = $this->getButtons();

        if (is_int($data)) {
            if (isset($buttons[$data])) {
                $button = $buttons[$data];
                $button->runCallable($player);
            } elseif (($callable = $this->getBackClosure()) !== null && $data === count($buttons)) {
                $callable($player);
            } else {
                throw new FormValidationException();
            }
        } elseif ($data === null) {
            if (($callable = $this->getCloseClosure()) !== null) {
                $callable($player);
            }
        } elseif (!empty($data)) {
            throw new FormValidationException();
        }
    }

    /**
     * @return Button[]
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    public function getCloseClosure(): ?Closure
    {
        return $this->onClose;
    }

    public function getBackClosure(): ?Closure
    {
        return $this->onBack;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        $buttons = $this->getButtons();
        if ($this->onBack !== null) {
            $buttons[] = new Button(self::BUTTON_BACK, $this->onBack);
        }

        return array_replace($data, [
            'type' => 'form',
            'title' => $this->getType() . $data['title'],
            'content' => $this->getContent(),
            'buttons' => array_map(function (Button $button): array {
                return $button->getData($this->getPlayer(), $this->getType());
            }, $buttons)
        ]);
    }

    public function addButton(Button $button): void
    {
        $this->buttons[] = $button;
    }

    public function setCloseClosure(?Closure $onClose): void
    {
        $this->onClose = $onClose;
    }
}
