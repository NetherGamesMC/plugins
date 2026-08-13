<?php

namespace uhc\task;

use libminigames\Arena;
use uhc\game\UHCArena;

class CountDownTask extends \libminigames\tasks\CountDownTask
{
    public function onRun(): void
    {
        parent::onRun();

        if ($this->countdown === 5 && $this->getHandler() !== null) {
            $this->getArena()->checkTypeVotes();
        }
    }

    /**
     * @return UHCArena
     */
    public function getArena(): Arena
    {
        /** @var UHCArena $arena */
        $arena = parent::getArena();

        return $arena;
    }
}