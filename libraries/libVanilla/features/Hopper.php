<?php
declare(strict_types=1);

namespace libVanilla\features;

use libVanilla\block\behaviour\HopperBehaviourManager;
use libVanilla\block\HopperConfig;
use libVanilla\listener\HopperListener;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\plugin\PluginBase;
use ReflectionClass;

class Hopper extends Feature
{

    protected function setup(PluginBase $plugin): void
    {
        /** @var RuntimeBlockStateRegistry $blockFactory */
        $blockFactory = RuntimeBlockStateRegistry::getInstance();
        HopperConfig::setInstance(new HopperConfig($plugin->getScheduler()));

        $hopper = VanillaBlocks::HOPPER();

        $refClass = new ReflectionClass(RuntimeBlockStateRegistry::class);
        $refProp = $refClass->getProperty("typeIndex");
        $typeIndex = $refProp->getValue($blockFactory);
        unset($typeIndex[$hopper->getTypeId()]);
        $refProp->setValue($blockFactory, $typeIndex);

        $blockFactory->register(new \libVanilla\block\Hopper($hopper->getIdInfo(), $hopper->getName(), new BlockTypeInfo($hopper->getBreakInfo())));

        HopperBehaviourManager::registerDefaults();

        $plugin->getServer()->getPluginManager()->registerEvents(new HopperListener($plugin), $plugin);
    }
}