<?php

namespace NetherGames\NGEssentials\entity\custom;

use Closure;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\GameSettings;
use pocketmine\player\Player;

trait NPCTrait
{
    /** @var ?Closure */
    protected ?Closure $callable = null;
    /** @var ?Closure */
    protected ?Closure $nameCallable = null;
    /** @var bool */
    private bool $allowLeftClick = true;

    /**
     * @param bool $allowLeftClick
     */
    public function setAllowLeftClick(bool $allowLeftClick): void
    {
        $this->allowLeftClick = $allowLeftClick;
    }

    public function setNameCallable(?Closure $nameCallable): void
    {
        $this->nameCallable = $nameCallable;
    }

    public function getUsername(): string
    {
        $name = parent::getUsername();

        if (($nameCallable = $this->nameCallable) !== null) {
            $name = $nameCallable($name);
        }

        return $name;
    }

    public function onHit(Player $player, bool $attack): void
    {
        if ($attack && !$this->allowLeftClick && !NGEssentials::getInstance()->getPlayerData()->getGameSettings()->getBool($player, GameSettings::LEFT_CLICK_NPC)) {
            return;
        }

        if (($callable = $this->getCallable()) !== null) {
            $callable($player);
        }
    }

    public function getCallable(): ?Closure
    {
        return $this->callable;
    }

    public function setCallable(?Closure $callable): void
    {
        $this->callable = $callable;
    }
}