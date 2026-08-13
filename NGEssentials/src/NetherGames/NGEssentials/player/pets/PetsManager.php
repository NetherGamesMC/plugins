<?php
/**
 *   _   _  _____ ______                    _   _       _
 *  | \ | |/ ____|  ____|                  | | (_)     | |
 *  |  \| | |  __| |__   ___ ___  ___ _ __ | |_ _  __ _| |___
 *  | . ` | | |_ |  __| / __/ __|/ _ \ '_ \| __| |/ _` | / __|
 *  | |\  | |__| | |____\__ \__ \  __/ | | | |_| | (_| | \__ \
 *  |_| \_|\_____|______|___/___/\___|_| |_|\__|_|\__,_|_|___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
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

namespace NetherGames\NGEssentials\player\pets;

use libDiscord\DiscordChannel;
use libDiscord\LimitAvoidableDiscordChannel;
use libDiscord\message\DiscordMessage;
use libDiscord\message\embed\Field;
use libDiscord\message\embed\MessageEmbed;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\PlayerManager;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\player\utils\PlayerBaseClass;
use NetherGames\NGEssentials\utils\discord\DiscordUtils;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

class PetsManager extends PlayerBaseClass
{
    /** @var DiscordChannel */
    private DiscordChannel $channel;
    /** @var Entity[] */
    private array $pets = [];

    public function __construct(PlayerManager $manager)
    {
        parent::__construct($manager);
        $this->getPlugin()->getServer()->getPluginManager()->registerEvents(new PetsListener($this), $this->getPlugin());

        $this->channel = new LimitAvoidableDiscordChannel([]);
    }

    public function removePet(Player $player): void
    {
        if (($pet = $this->getPetFrom($player)) !== null) {
            if (!$pet->isClosed()) {
                $pet->flagForDespawn();
            }

            unset($this->pets[$player->getId()]);
        }
    }

    public function getPetFrom(Player $player): ?Entity
    {
        return $this->pets[$player->getId()] ?? null;
    }

    public function spawnPet(Player $player): void
    {
        $playerData = $this->getPlugin()->getPlayerData();
        if ($playerData->getBool($player, PlayerData::NICK)) {
            return;
        }

        $pet = CosmeticHandler::PETS()->getPetEntity($player);
        if ($pet === null) {
            if (($petSaveId = $playerData->getString($player, PlayerData::PET)) === '') {
                return;
            }

            $petFactory = PetFactory::getInstance()->get($petSaveId);

            if ($petFactory === null || !$petFactory->hasPermission($player)) {
                $playerData->setValue($player, PlayerData::PET, '');
                return;
            } else {
                $pet = $petFactory->create($player);
            }
        }

        $this->pets[$player->getId()] = $pet;
        $pet->setNameTag($playerData->getString($player, PlayerData::PET_NAME));
        $pet->spawnToAll();
    }

    public function setPetName(Player $player, string $name = ''): void
    {
        $this->getPlugin()->getPlayerData()->setValue($player, PlayerData::PET_NAME, $name);

        if ($name !== '') {
            Translator::sendMessage($player, "forms.settings.pet.setname", Translator::TYPE_SUCCESS, ...["name" => $name]);

            $this->channel->post(DiscordMessage::embed(MessageEmbed::rich("Pet Logger")
                ->addFields(
                    Field::simple("Player", $player->getName()),
                    Field::simple("Pet Name", $name)
                )
                ->setThumbnail(DiscordUtils::asThumbnail($player->getName()))
            ));
        }

        if (($pet = $this->getPetFrom($player)) !== null) {
            $pet->setNameTag($name);
        }
    }
}
