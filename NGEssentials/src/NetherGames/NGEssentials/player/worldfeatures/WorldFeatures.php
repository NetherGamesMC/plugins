<?php

declare(strict_types=1);

namespace NetherGames\NGEssentials\player\worldfeatures;

use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\utils\PlayerBaseClass;
use NetherGames\NGEssentials\player\worldfeatures\zones\ZonesManager;
use NetherGames\NGEssentials\ServerManager;

class WorldFeatures extends PlayerBaseClass
{
    /** @var ZonesManager|null */
    private ?ZonesManager $zonesManager = null;

    public function __construct(PlayerManager $manager)
    {
        parent::__construct($manager);

        $plugin = $manager->getPlugin();

        if ($plugin->getServerManager()->getServerType() === ServerManager::LOBBY) {
            $this->zonesManager = new ZonesManager($this);
        }
    }

    public function getZonesManager(): ?ZonesManager
    {
        return $this->zonesManager;
    }
}
