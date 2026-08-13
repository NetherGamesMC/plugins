<?php

namespace NetherGames\NGEssentials\tasks;

use NetherGames\NGEssentials\block\CustomBlockRegistry;
use pmmp\thread\ThreadSafeArray;
use pocketmine\scheduler\AsyncTask;

final class BlockRegisterAsyncTask extends AsyncTask
{
    private ThreadSafeArray $blockFuncs;
    private ThreadSafeArray $names;
    private ThreadSafeArray $serializer;
    private ThreadSafeArray $deserializer;

    public function __construct()
    {
        $this->blockFuncs = new ThreadSafeArray();
        $this->names = new ThreadSafeArray();
        $this->serializer = new ThreadSafeArray();
        $this->deserializer = new ThreadSafeArray();

        foreach (CustomBlockRegistry::getBlockFunctions() as $identifier => [$blockFunc, $name, $serializer, $deserializer]) {
            $this->blockFuncs[$identifier] = $blockFunc;
            $this->names[$identifier] = $name;
            $this->serializer[$identifier] = $serializer;
            $this->deserializer[$identifier] = $deserializer;
        }
    }

    public function onRun(): void
    {
        foreach ($this->blockFuncs as $identifier => $blockFunc) {
            CustomBlockRegistry::registerBlock(
                $blockFunc,
                $this->names[$identifier],
                $identifier,
                serializer: $this->serializer[$identifier],
                deserializer: $this->deserializer[$identifier],
                registerToRegistry: false
            );
        }
    }
}