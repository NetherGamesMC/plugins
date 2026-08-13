<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author k3ithos, matcracker, driesboy
 *
 */
declare(strict_types=1);

namespace NetherGames\NGEssentials\player\cosmetics\types\effect;

use Closure;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\SimpleForm;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use function mt_rand;

class WinEffectsCosmetic extends EffectCosmetic
{
    public function run(Player $player, ?Position $pos = null): void
    {
        if ($pos === null) {
            $this->getHandler()->getPlugin()->getScheduler()->scheduleRepeatingTask(new class($this, $player) extends Task {
                /** @var int */
                private int $worldId;
                /** @var int */
                private int $time = 0;

                public function __construct(private WinEffectsCosmetic $instance, private Player $player)
                {
                    $this->worldId = $player->getWorld()->getId();
                }

                public function onRun(): void
                {
                    if ($this->time === 12 || !$this->player->isConnected() || $this->player->getWorld()->getId() !== $this->worldId) {
                        $this->getHandler()->cancel();
                    } else {
                        $this->instance->run($this->player, Position::fromObject($this->player->getPosition()->add(mt_rand(-1, 1), 0, mt_rand(-1, 1)), $this->player->getWorld()));
                        $this->time++;
                    }
                }
            }, 10);
        } else {
            parent::run($player, $pos);
        }
    }

    public function getName(): string
    {
        return 'Win Effect';
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.win_effect';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/items/fireworks', $callable);
    }
}