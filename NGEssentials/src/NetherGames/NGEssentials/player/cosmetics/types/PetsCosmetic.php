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

namespace NetherGames\NGEssentials\player\cosmetics\types;

use Closure;
use InvalidArgumentException;
use libforms\elements\Button;
use libforms\elements\ImageButton;
use libforms\elements\Input;
use libforms\elements\Toggle;
use libforms\FormManager;
use libforms\SimpleForm;
use NetherGames\NGEssentials\player\cosmetics\traits\EntityCosmeticTrait;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\BouncingAnimal;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\BouncingMonster;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\HoveringAnimal;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\HoveringMonster;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\PetMovementType;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\PetType;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\SwimmingAnimal;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\SwimmingMonster;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\WalkingAnimal;
use NetherGames\NGEssentials\player\cosmetics\utils\pet\WalkingMonster;
use NetherGames\NGEssentials\player\cosmetics\utils\PlayerCosmeticEntry;
use NetherGames\NGEssentials\player\cosmetics\utils\PlayerCosmeticStatus;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\pets\PetCreator;
use NetherGames\NGEssentials\player\pets\PetFactory;
use NetherGames\NGEssentials\player\pets\PetsManager;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_filter;
use function ltrim;
use function strlen;
use function usort;

class PetsCosmetic extends Cosmetic
{
    use EntityCosmeticTrait;

    private const PET_KEY = 'pet';
    private const PET_TYPE = 'type';
    private const PET_MOVEMENT_TYPE = 'movement_type';

    public function onSelect(Player $player): bool
    {
        $petsManager = $this->getPetsManager();
        $petsManager->removePet($player);
        $petsManager->spawnPet($player);
        return true;
    }

    private function getPetsManager(): PetsManager
    {
        return $this->handler->getManager()->getPetsManager();
    }

    private function getPlayerData(): PlayerData
    {
        return $this->handler->getPlugin()->getPlayerData();
    }

    public function getPetEntity(Player $player): ?Entity
    {
        if (($entry = $this->getSelectedEntry($player)) === null) {
            return null;
        }

        $petType = $this->getPetType($dataEntry = $entry->getDataEntry());

        $baseClass = match ($this->getMovementType($dataEntry)) {
            PetMovementType::BOUNCING => match ($petType) {
                PetType::ANIMAL => BouncingAnimal::class,
                PetType::MONSTER => BouncingMonster::class,
            },
            PetMovementType::HOVERING => match ($petType) {
                PetType::ANIMAL => HoveringAnimal::class,
                PetType::MONSTER => HoveringMonster::class,
            },
            PetMovementType::SWIMMING => match ($petType) {
                PetType::ANIMAL => SwimmingAnimal::class,
                PetType::MONSTER => SwimmingMonster::class,
            },
            PetMovementType::WALKING => match ($petType) {
                PetType::ANIMAL => WalkingAnimal::class,
                PetType::MONSTER => WalkingMonster::class,
            },
        };

        return new $baseClass(
            $player->getLocation(),
            $player,
            $this->generatePetData($dataEntry)
        );
    }

    private function getPetType(CosmeticDataEntry $entry): PetType
    {
        return PetType::tryFrom($entry->data[self::PET_KEY][self::PET_TYPE] ?? '') ?? throw new InvalidArgumentException('Pet type not found');
    }

    private function getMovementType(CosmeticDataEntry $entry): PetMovementType
    {
        return PetMovementType::tryFrom($entry->data[self::PET_KEY][self::PET_MOVEMENT_TYPE] ?? '') ?? throw new InvalidArgumentException('Pet movement type not found');
    }

    private function generatePetData(CosmeticDataEntry $entry): CompoundTag
    {
        return WalkingAnimal::addCosmeticNBT(
            CompoundTag::create(),
            $this->getEntityId($entry),
            $this->getEntitySizeInfo($entry)
        );
    }

    public function addCosmeticsToForm(SimpleForm $form, Player $player, callable $callable): void
    {
        $entries = $this->getPlayerCosmeticEntries($player);

        if (!Permissions::isStaff($player)) {
            $entries = array_filter($entries, static fn(PlayerCosmeticEntry $entry): bool => $entry->status !== PlayerCosmeticStatus::LOCKED);
        }

        usort($entries, static function (PlayerCosmeticEntry $a, PlayerCosmeticEntry $b): int {
            return $a->status <=> $b->status;
        });

        $playerData = $this->getPlayerData();
        if (($selectedPetSaveId = $playerData->getString($player, PlayerData::PET)) !== '') {
            if (($selectedPetCreator = PetFactory::getInstance()->get($selectedPetSaveId)) === null) {
                $playerData->setValue($player, PlayerData::PET, '');
            } else {
                $this->addRankedPetToForm($form, $selectedPetCreator, $callable, true);
            }
        }

        foreach ($entries as $entry) {
            $this->addCosmeticEntryToForm($form, $player, $entry, $callable);
        }

        foreach (PetFactory::getInstance()->getAll($player) as $petCreator) {
            if ($petCreator->getSaveId() !== $selectedPetSaveId) {
                $this->addRankedPetToForm($form, $petCreator, $callable, false);
            }
        }
    }

    protected function addCosmeticEntryToForm(SimpleForm $form, Player $player, PlayerCosmeticEntry $playerEntry, callable $callable): void
    {
        $isStaff = Permissions::isStaff($player);

        $color = match (true) {
            $playerEntry->status === PlayerCosmeticStatus::SELECTED => TextFormat::GREEN,
            $playerEntry->status === PlayerCosmeticStatus::UNLOCKED || $isStaff => TextFormat::YELLOW,
            default => throw new RuntimeException('Invalid status')
        };

        $onClick = function (Player $player) use ($playerEntry, $isStaff, $callable) {
            if ($playerEntry->status === PlayerCosmeticStatus::LOCKED && !$isStaff) {
                $callable($player);
            } else {
                $entry = $playerEntry->entry;

                if ($playerEntry->status === PlayerCosmeticStatus::SELECTED) {
                    $this->sendPetNameForm($player, $entry->name, $callable);
                } else {
                    $this->setSelected($player, $entry);
                    $this->getPlayerData()->setValue($player, PlayerData::PET, '');

                    $player->sendMessage('§aSelected §6' . $entry->name . ' §afor the §6' . $this->getName() . ' §acosmetic.');

                    if ($this->onSelect($player)) {
                        $callable($player);
                    }
                }
            }
        };

        $entry = $playerEntry->entry;
        $form->addButton($entry->imageType === null ? (
        new Button($color . $entry->name, $onClick)
        ) : (
        new ImageButton($color . $entry->name, $entry->imageType, $entry->imageSource, $onClick)
        ));
    }

    private function addRankedPetToForm(SimpleForm $form, PetCreator $petCreator, callable $callable, bool $isSelected): void
    {
        $onClick = function (Player $player) use ($petCreator, $isSelected, $callable) {
            if ($isSelected) {
                $this->sendPetNameForm($player, $petCreator->getDisplayableName(), $callable);
            } else {
                $this->setSelected($player, null);
                $this->getPlayerData()->setValue($player, PlayerData::PET, $petCreator->getSaveId());

                $player->sendMessage('§aSelected §6' . $petCreator->getDisplayableName() . ' §afor the §6' . $this->getName() . ' §acosmetic.');

                if ($this->onSelect($player)) {
                    $callable($player);
                }
            }
        };

        $form->addButton(new Button(($isSelected ? TextFormat::GREEN : TextFormat::YELLOW) . $petCreator->getDisplayableName(), $onClick));
    }

    private function sendPetNameForm(Player $player, string $entryName, callable $onBack): void
    {
        $form = FormManager::createCustomForm($player, $onBack);

        if ($form !== null) {
            $petName = $this->getPlayerData()->getString($player, PlayerData::PET_NAME);

            $form->addElement(new Input(Translator::getTranslationPlayer($player, "forms.settings.pet.change"), $petName, $petName, function (Player $player, string $value) {
                $value = ltrim(TextFormat::clean($value));

                if ($value === 'off' || $value === 'reset' || $value === '') {
                    $this->getPetsManager()->setPetName($player);
                } else if (strlen($value) > 15) {
                    Translator::sendMessage($player, "forms.settings.pet.toolong", Translator::TYPE_ERROR);
                } else {
                    $this->handler->getManager()->checkName($player, $value, function () use ($player, $value) {
                        $this->getPetsManager()->setPetName($player, $value);
                    });
                }
            }));

            $form->addElement(new Toggle("Remove Pet", false, function (Player $player, bool $value) use ($entryName) {
                if ($value) {
                    $this->setSelected($player, null);
                    $this->getPlayerData()->setValue($player, PlayerData::PET, '');

                    $player->sendMessage('§aTurned the §6' . $entryName . ' §acosmetic off.');

                    $this->getPetsManager()->removePet($player);
                }
            }));

            $form->sendForm();
        }
    }

    public function getCrateAnimation(): string
    {
        return 'animation.ng.lobby.crate.pet';
    }

    public function getName(): string
    {
        return 'Pets';
    }

    public function getButton(Player $player, Closure $callable): Button
    {
        return new ImageButton(SimpleForm::BUTTON_TAB . $this->getName(), ImageButton::IMAGE_TYPE_PATH, 'textures/ui/ng/tabs/pets', $callable);
    }
}
