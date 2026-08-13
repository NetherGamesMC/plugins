<?php

namespace lobby\utils;

use NetherGames\NGEssentials\thread\NGThreadPool;
use pocketmine\block\BlockBreakInfo as BreakInfo;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo as Info;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockTypeNames as Ids;
use pocketmine\item\StringToItemParser;
use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

class Blocks
{
    //private static EndPortal $endPortal;

    public static function register(Server $server): void
    {
        self::registerOnCurrentThread();

        $hook = function (int $worker, AsyncPool $pool): void {
            $pool->submitTaskToWorker(new class extends AsyncTask {
                public function onRun(): void
                {
                    Blocks::registerOnCurrentThread();
                }
            }, $worker);
        };

        NGThreadPool::getInstance()->addWorkerStartHook(function (int $worker) use ($hook): void {
            $hook($worker, NGThreadPool::getInstance());
        });
        $server->getAsyncPool()->addWorkerStartHook(function (int $worker) use ($server, $hook): void {
            $hook($worker, $server->getAsyncPool());
        });
    }

    public static function registerOnCurrentThread(): void
    {
        //self::$endPortal = new EndPortal(new BID(BlockTypeIds::newId()), "End Portal", new Info(BreakInfo::indestructible()));

        ///** @var RuntimeBlockStateRegistry $registry */
        //$registry = RuntimeBlockStateRegistry::getInstance();
        //$registry->register(self::$endPortal);

        //$serializer = GlobalBlockStateHandlers::getSerializer();
        //$serializer->mapSimple(self::$endPortal, Ids::END_PORTAL);

        //$deserializer = GlobalBlockStateHandlers::getDeserializer();
        //$deserializer->mapSimple(Ids::END_PORTAL, fn() => self::$endPortal);

        //$parser = StringToItemParser::getInstance();
        //$parser->registerBlock("end_portal", fn() => self::END_PORTAL());
    }

    /*public static function END_PORTAL(): EndPortal
    {
        return clone self::$endPortal;
    }*/
}