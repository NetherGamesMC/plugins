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

namespace NetherGames\NGEssentials\commands;

use NetherGames\NGEssentials\entity\custom\Custom;
use NetherGames\NGEssentials\entity\custom\HumanNPC;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\player\cosmetics\CosmeticHandler;
use NetherGames\NGEssentials\player\cosmetics\types\Cosmetic;
use NetherGames\NGEssentials\player\cosmetics\types\CosmeticEntry;
use NetherGames\NGEssentials\player\NGPlayer;
use NetherGames\NGEssentials\player\permissions\Permissions;
use NetherGames\NGEssentials\player\PlayerData;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use function array_shift;
use function count;
use function hexdec;
use function is_numeric;
use function mb_chr;

class TestCommand extends BaseCommand
{
    public function __construct(NGEssentials $plugin)
    {
        parent::__construct('test', $plugin);

        $this->setPermission(Permissions::RANK_OWNER);
        $this->setPermissionMessage('command.reserved.estaff');
        $this->setDescription("Command used for testing purposes.");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof NGPlayer) {
            $sender->sendMessage($this->getPlugin()->getPrefix() . '§cThat command can only be run in-game.');
            return false;
        }

        return match (array_shift($args)) {
            'icon' => $this->testIcon($sender, $args),
            'entity' => $this->testEntity($sender, $args),
            'cosmetic' => $this->testCosmetic($sender, $args),
            default => (function () use ($sender): bool {
                $sender->sendMessage('§cUsage: /test <icon|entity|cosmetic>');
                return false;
            })()
        };
    }

    private function testIcon(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test icon <icon> <message|title>');
            return false;
        }

        // \u{E1FE}
        // input will be like: 0xE1FE
        $icon = mb_chr(hexdec(array_shift($args)));

        match (array_shift($args)) {
            'title' => $sender->sendTitle($icon),
            default => $sender->sendMessage($icon),
        };
        return true;
    }

    private function testEntity(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test entity <entity_runtime_id>');
            return false;
        }

        $this->spawnEntity(array_shift($args), $sender->getLocation());
        return true;
    }

    private function spawnEntity(string $runtimeId, Location $location): void
    {
        $this->getPlugin()->getEntityManager()->addEntity(new class($runtimeId, $location) extends Custom {
            public function __construct(private string $runtimeId, Location $location)
            {
                parent::__construct($location, $runtimeId);
            }

            public function getSpawnPacket(TypeConverter $typeConverter): ClientboundPacket
            {
                return AddActorPacket::create(
                    $this->getId(),
                    $this->getId(),
                    $this->runtimeId,
                    $this->location->asVector3(),
                    null,
                    $this->location->getPitch(),
                    $this->location->getYaw(),
                    $this->location->getYaw(),
                    $this->location->getYaw(),
                    [],
                    $this->metadata->getAll(),
                    new PropertySyncData([], []),
                    []
                );
            }
        });
    }

    private function spawnHumanNPC(Location $location, Skin $skin): void
    {
        $this->getPlugin()->getEntityManager()->addEntity(new HumanNPC(
            $location,
            '',
            $skin
        ));
    }

    private function testCosmetic(NGPlayer $sender, array $args): bool
    {
        return match (array_shift($args)) {
            'shopkeeper' => $this->testCosmeticShopkeeper($sender, $args),
            'cage' => $this->testCosmeticCage($sender, $args),
            'flag' => $this->testCosmeticFlag($sender, $args),
            'cape' => $this->testCosmeticCape($sender, $args),
            'attachable' => $this->testCosmeticAttachable($sender, $args),
            'give' => $this->testCosmeticGive($sender, $args),
            'key' => $this->testCosmeticKey($sender, $args),
            'clear' => $this->testCosmeticClear($sender, $args),
            default => (function () use ($sender): bool {
                $sender->sendMessage('§cUsage: /test cosmetic <shopkeeper|cage|flag|give|key|cape|attachable|clear>');
                return false;
            })()
        };
    }

    private function testCosmeticKey(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic give key <amount>');
            return false;
        }

        $argument = array_shift($args);
        if (!is_numeric($argument)) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $playerData = $this->getPlugin()->getPlayerData();
        $playerData->addInt($sender, PlayerData::KEYS, (int)$argument);
        $sender->sendMessage('§aAdded ' . $argument . ' keys to your account.');
        return true;
    }

    /**
     * @return CosmeticEntry[]|null
     */
    private function getEntriesFromArgument(Cosmetic $cosmetic, string $argument): null|array
    {
        return match (true) {
            is_numeric($argument) => ($cosmeticEntry = $cosmetic->getEntry((int)$argument)) === null ? null : [$cosmeticEntry],
            $argument === 'all' => $cosmetic->getEntries(),
            default => null,
        };
    }

    private function testCosmeticGive(NGPlayer $sender, array $args): bool
    {
        $argument = array_shift($args);

        return match (true) {
            is_numeric($argument) => $this->testCosmeticGiveCosmetic($sender, (int)$argument, $args),
            $argument === 'all' => $this->testCosmeticGiveAll($sender),
            default => (function () use ($sender): bool {
                $sender->sendMessage('§cUsage: /test cosmetic give <all|cosmetic_id>');
                return false;
            })()
        };
    }

    private function testCosmeticClear(NGPlayer $sender, array $args): bool
    {
        $argument = array_shift($args);

        return match (true) {
            is_numeric($argument) => $this->testCosmeticClearCosmetic($sender, (int)$argument),
            $argument === 'all' => $this->testCosmeticClearAll($sender),
            default => (function () use ($sender): bool {
                $sender->sendMessage('§cUsage: /test cosmetic clear all');
                return false;
            })()
        };
    }

    private function testCosmeticClearCosmetic(NGPlayer $sender, int $cosmeticId): bool
    {
        if (($cosmetic = CosmeticHandler::getCosmeticById($cosmeticId)) === null) {
            $sender->sendMessage('§cInvalid cosmetic id.');
            return false;
        }

        $playerData = $this->getPlugin()->getPlayerData();

        $data = $playerData->getArray($sender, PlayerData::COSMETICS);
        unset($data[$cosmetic->getSaveId()]);
        $playerData->setValue($sender, PlayerData::COSMETICS, $data, true);

        $sender->sendMessage('§aCleared cosmetic from you.');
        return true;
    }

    private function testCosmeticClearAll(NGPlayer $sender): bool
    {
        $this->getPlugin()->getPlayerData()->setValue($sender, PlayerData::COSMETICS, [], true);

        $sender->sendMessage('§aCleared all cosmetics from you.');
        return true;
    }

    private function testCosmeticGiveCosmetic(NGPlayer $sender, int $cosmeticId, array $args): bool
    {
        if (($cosmetic = CosmeticHandler::getCosmeticById($cosmeticId)) === null) {
            $sender->sendMessage('§cInvalid cosmetic id.');
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic give <cosmetic_id> <entry_id|all>');
            return false;
        }

        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, array_shift($args))) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        foreach ($cosmeticEntries as $entry) {
            $cosmetic->give($sender, $entry);
        }

        $sender->sendMessage('§aGiven cosmetic to you.');
        return true;
    }

    private function testCosmeticGiveAll(NGPlayer $sender): bool
    {
        foreach (CosmeticHandler::getAll() as $cosmetic) {
            foreach ($cosmetic->getEntries() as $entry) {
                $cosmetic->give($sender, $entry);
            }
        }

        $sender->sendMessage('§aGiven all cosmetics to you.');
        return true;
    }

    private function testCosmeticShopkeeper(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic shopkeeper <entry_id|all>');
            return false;
        }

        $cosmetic = CosmeticHandler::SHOPKEEPERS();
        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, array_shift($args))) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $vector = $sender->getLocation()->asVector3();
        foreach ($cosmeticEntries as $entry) {
            $this->spawnEntity($cosmetic->getEntityId($entry->getDataEntry()), Location::fromObject($vector = $vector->add(2, 0, 0), $sender->getWorld()));
        }
        $sender->sendMessage('§aSpawned shopkeeper(s).');
        return true;
    }

    private function testCosmeticFlag(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic flag <entry_id|all>');
            return false;
        }

        $cosmetic = CosmeticHandler::FLAGS();
        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, array_shift($args))) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $vector = $sender->getLocation()->asVector3();
        foreach ($cosmeticEntries as $entry) {
            $this->spawnEntity($cosmetic->getEntityForTeam($entry, true), Location::fromObject($vector = $vector->add(2, 0, 0), $sender->getWorld()));
            $this->spawnEntity($cosmetic->getEntityForTeam($entry, false), Location::fromObject($vector = $vector->add(2, 0, 0), $sender->getWorld()));
        }
        $sender->sendMessage('§aSpawned flag(s).');
        return true;
    }

    private function testCosmeticCape(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic cape <entry_id|all>');
            return false;
        }

        $cosmetic = CosmeticHandler::CAPES();
        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, array_shift($args))) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $vector = $sender->getLocation()->asVector3();
        foreach ($cosmeticEntries as $entry) {
            $this->spawnHumanNPC(Location::fromObject($vector = $vector->add(2, 0, 0), $sender->getWorld()), $cosmetic->getSkin($entry->getDataEntry(), $sender->getOriginalSkin()));
        }
        $sender->sendMessage('§aSpawned cape(s).');
        return true;
    }

    private function testCosmeticAttachable(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 1) {
            $sender->sendMessage('§cUsage: /test cosmetic attachable <entry_id|all>');
            return false;
        }

        $cosmetic = CosmeticHandler::ATTACHABLES();
        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, array_shift($args))) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $vector = $sender->getLocation()->asVector3();
        foreach ($cosmeticEntries as $entry) {
            $this->spawnHumanNPC(Location::fromObject($vector = $vector->add(2, 0, 0), $sender->getWorld()), $cosmetic->getMergedSkin($entry->getDataEntry(), $sender->getOriginalSkin()));
        }
        $sender->sendMessage('§aSpawned attachable(s).');
        return true;
    }

    private function testCosmeticCage(NGPlayer $sender, array $args): bool
    {
        if (count($args) < 2) {
            $sender->sendMessage('§cUsage: /test cosmetic cage <solo|team> <entry_id|all|despawn|animation>');
            return false;
        }

        $cosmetic = match (array_shift($args)) {
            'solo' => CosmeticHandler::SOLO_CAGES(),
            'team' => CosmeticHandler::TEAM_CAGES(),
            default => null,
        };

        if ($cosmetic === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $argument = array_shift($args);
        if ($argument === 'despawn') {
            $cosmetic->despawnCages($sender->getWorld());
            $sender->sendMessage('§aDespawned cage(s).');
            return true;
        } else if ($argument === 'animation') {
            $cosmetic->runSpawnAnimation($sender->getWorld());
            $sender->sendMessage('§aAnimated cage(s).');
            return true;
        }

        if (($cosmeticEntries = $this->getEntriesFromArgument($cosmetic, $argument)) === null) {
            $sender->sendMessage('§cInvalid argument.');
            return false;
        }

        $cages = [];
        $vector = $sender->getLocation()->asVector3();
        foreach ($cosmeticEntries as $entry) {
            foreach ($entry->getDataEntries() as $e) {
                $cages[] = $cosmetic->getCageByEntry($e, Location::fromObject($vector = $vector->add(8, 0, 0), $sender->getWorld()));
            }
        }

        $cosmetic->spawnCages($sender->getWorld(), $cages);
        $sender->sendMessage('§aSpawned cage(s).');
        return true;
    }
}