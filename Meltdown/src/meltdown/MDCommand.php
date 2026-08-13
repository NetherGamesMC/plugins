<?php

namespace meltdown;

use libasyncio\FileCopyAsyncTask;
use libminigames\Command;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MDCommand extends Command
{

    public function __construct(Meltdown $plugin)
    {
        parent::__construct($plugin);
        $this->setUsage($this->getHelpMessage());
    }

    private function getHelpMessage(?string $subCommand = null): string
    {
        return match ($subCommand) {
            "create" => "/meltdown edit create <mapName>",
            "setspawn" => "/meltdown edit setspawn <mapName>",
            "setradius" => "/meltdown edit setradius <mapName> <radius>",
            "setfloors" => "/meltdown edit setfloors <mapName> <floor1Y> [floor2Y...]",
            default => "/meltdown edit <create|setspawn|setradius|setfloors>"
        };
    }

    /**
     * @param Player $sender
     * @param string $subCommand
     * @param string[] $args
     * @return bool
     */
    public function onCommand(Player $sender, string $subCommand, array $args): bool
    {
        if (!$sender->hasPermission("nethergames.md.admin")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
            return true;
        }

        switch (strtolower($subCommand)) {
            case 'create':
                if (!isset($args[0])) {
                    $this->help($sender, "create");
                    return true;
                }

                $mapName = array_shift($args);
                if ($this->getPlugin()->getArenaConfig()->isMap($mapName)) {
                    $this->error($sender, "This map already exists!");
                    return true;
                }

                $fromPath = "{$this->getPlugin()->getServer()->getDataPath()}/worlds/{$sender->getWorld()->getFolderName()}";
                $toPath = "{$this->getPlugin()->getDataFolder()}/arenas/$mapName";

                // We need to force save because WM->unloadWorld calls World->save(false)
                $sender->getWorld()->save(true);
                $sender->getServer()->getWorldManager()->unloadWorld($sender->getWorld(), true);

                if (!is_dir("{$this->getPlugin()->getDataFolder()}/arenas")) {
                    mkdir("{$this->getPlugin()->getDataFolder()}/arenas"); // this isn't async but should be fine
                }

                $this->getPlugin()->getServer()->getAsyncPool()->submitTask(new FileCopyAsyncTask($fromPath, $toPath, function () use ($sender, $mapName) {
                    $this->getPlugin()->getArenaConfig()->createArena($mapName);
                    $this->success($sender, "Successfully created map $mapName");
                }));
                break;
            case 'setspawn':
                if (!isset($args[0])) {
                    $this->help($sender, "setspawn");
                    return true;
                }

                $mapName = array_shift($args);
                if (!$this->getPlugin()->getArenaConfig()->isMap($mapName)) {
                    $this->error($sender, "Map $mapName does not exist!");
                    return true;
                }

                $location = $sender->getLocation();
                $this->getPlugin()->getArenaConfig()->setSpawn($mapName, $location);
                $prettyVec3 = "<x:{$location->getX()}, y:{$location->getY()}, z:{$location->getZ()}>";
                $this->success($sender, "Spawn successfully set to $prettyVec3 for map $mapName");
                break;
            case 'setradius':
                if (!isset($args[0])) {
                    $this->help($sender, "setradius");
                    return true;
                }

                $mapName = array_shift($args);
                if (!$this->getPlugin()->getArenaConfig()->isMap($mapName)) {
                    $this->error($sender, "Map $mapName does not exist!");
                    return true;
                }

                $radiusStr = array_shift($args);
                if (!is_numeric($radiusStr)) {
                    $this->error($sender, "$radiusStr is not an integer radius!");
                    return true;
                }

                $radius = (int)$radiusStr;
                $this->getPlugin()->getArenaConfig()->setRadius($mapName, $radius);
                $prettyRadius = (string)$radius; // display it as an int even if a float was given
                $this->success($sender, "Successfully set radius to $prettyRadius for map $mapName");
                break;
            case 'setfloors':
                if (!isset($args[0])) {
                    $this->help($sender, "setfloors");
                    return true;
                }

                $mapName = array_shift($args);
                if (!$this->getPlugin()->getArenaConfig()->isMap($mapName)) {
                    $this->error($sender, "Map $mapName does not exist!");
                    return true;
                }

                /** @var int[] $floors */
                $floors = [];
                foreach ($args as $floorString) {
                    if (is_numeric($floorString)) {
                        $floors[] = (int)$floorString;
                    } else {
                        $this->help($sender, "setfloors");
                        return true;
                    }
                }

                $this->getPlugin()->getArenaConfig()->setFloors($mapName, $floors);
                $floorCount = count($floors);
                $prettyFloors = implode(", ", $args);
                $this->success($sender, "Successfully set $floorCount layers for map $mapName: $prettyFloors");
                break;
            default:
                $this->help($sender);
                break;
        }

        return true;
    }

    public function getPlugin(): Meltdown
    {
        /** @var Meltdown $plugin */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $plugin = parent::getPlugin();
        return $plugin;
    }

    private function help(Player $player, ?string $subCommand = null): void
    {
        $message = $this->getHelpMessage($subCommand);

        $player->sendMessage(TextFormat::WHITE . "Usage: " . TextFormat::YELLOW . $message);
    }

    private function error(Player $player, string $message): void
    {
        $player->sendMessage(TextFormat::RED . $message);
    }

    private function success(Player $player, string $message): void
    {
        $player->sendMessage(TextFormat::GREEN . $message);
    }
}