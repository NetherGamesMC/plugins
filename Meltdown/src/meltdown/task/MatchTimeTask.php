<?php

namespace meltdown\task;

use libminigames\utils\StatsData as StatsDataAlias;
use meltdown\arena\MDArena;
use meltdown\Meltdown;
use meltdown\utils\StatsData;
use pocketmine\entity\Location;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\IgniteSound;
use function abs;
use function array_map;
use function array_shift;
use function count;
use function implode;

class MatchTimeTask extends \libminigames\tasks\MatchTimeTask{
    /** @var int The TNT fuse time (in ticks) at the beginning of overtime */
    public const OVERTIME_TNT_BASE_FUSE = 80;

    /**
     * @var float What the TNT fuse time is multiplied by every minute
     * If this is 0.8 and OVERTIME_TNT_BASE_FUSE is 100, after 2 minutes, the fuse time will be 100 * 0.8**2 = 64 ticks
     */
    public const OVERTIME_TNT_FUSE_DECAY = 0.8;

    /** @var int We can't use ($this->timePassed - $this->time) because timePassed doesn't increment after overtime */
    private int $timePassedAfterOvertime = 0;

    public function __construct(MDArena $arena){
        parent::__construct($arena);
        $this->timePassed = -5;
    }

    public function gameTick() : void{
        $arena = $this->getArena();

        $this->tryIncrementTimePlayed();

        if($this->timePassed < 0){
            if ($this->timePassed === -1) {
                $arena->broadcastMessage('§eBlocks start disappearing in ' . TextFormat::RED . '1 §esecond!');
            } else {
                $arena->broadcastMessage('§eBlocks start disappearing in ' . TextFormat::RED . abs($this->timePassed) . ' §eseconds!');
            }

            if ($this->timePassed === -3) {
                foreach ($arena->getAlivePlayers() as $player) {
                    $player->setNoClientPredictions(false);
                }
            }

            $arena->getScoreboardHandler()->updateTime(abs($this->timePassed), false, true);
        }else{
            $arena->getScoreboardHandler()->updateTime($this->time - $this->timePassed);

            $arena->getBlockHandler()->checkLastMovements();

            if($this->timePassed % 15 === 0){
                if($this->timePassed === 0){
                    $arena->getPowerupHandler()->setBlocksInPlay();
                    $arena->getBlockHandler()->processRequests = true;

                    foreach($arena->getAlivePlayers() as $player){
                        $arena->getWorld()->addSound($player->getLocation(), new BlazeShootSound(), [$player]);
                    }
                }

                $arena->getPowerupHandler()->dropPowerups();
            }
        }

        if(count($arena->getAlivePlayers()) <= 1){
            $this->finishArena();
        }
    }

    public function getArena() : MDArena{
        /** @var MDArena $arena */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $arena = parent::getArena();

        return $arena;
    }

    public function tryIncrementTimePlayed() : void{
        if($this->timePassed % 60 === 0){
            foreach($this->getArena()->getAlivePlayers() as $player){
                $this->getArena()->incrementMinutesPlayed($player);
            }
        }
    }

    public function finishArena() : void{
        $arena = $this->getArena();
        $statsData = $arena->getStatsData();

        $alivePlayers = $arena->getAlivePlayers();
        $alivePlayerXuids = array_map(fn(Player $player) => $player->getXuid(), $alivePlayers);
        foreach($arena->getXuids() as $xuid){
            if(in_array($xuid, $alivePlayerXuids)){
                $statsData->addValue($xuid, StatsDataAlias::WINS);
                $statsData->addValue($xuid, StatsData::MD_WINS);
            }else{
                $statsData->addValue($xuid, StatsDataAlias::LOSSES);
                $statsData->addValue($xuid, StatsData::MD_LOSSES);
            }
        }

        // This can happen if both players fall off on the same tick
        if(count($alivePlayers) === 0){
            $this->getArena()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "Unfortunately, nobody has won this game!", true);
        }else if(count($alivePlayers) === 1){
            $winner = array_shift($alivePlayers);
            $winner->sendTitle('§l§6VICTORY!', '§7You were the last player standing!', 0, 100, 20);
        }else{ // This really shouldn't happen
            $winnerList = implode(", ", array_map((fn($winner) => $winner->getName()), $alivePlayers));
            $this->getArena()->broadcastMessage(
                TextFormat::GOLD . TextFormat::BOLD . "We have our winners! " . TextFormat::AQUA . $winnerList,
                true
            );

            foreach($alivePlayers as $winner){
                $winner->sendTitle('§l§6VICTORY!', '§7You were one of the last players standing!', 0, 100, 20);
            }
        }

        $arena->getScoreboardHandler()->updateTime(0);

        parent::finishArena();
    }

    public function overTimeTick() : void{
        $this->timePassedAfterOvertime++;

        $arena = $this->getArena();

        if($this->timePassedAfterOvertime === 1){
            foreach($arena->getAlivePlayers() as $player){
                $player->sendTitle(TextFormat::RED . "Overtime!", TextFormat::YELLOW . "Avoid the TNT!");
            }
        }

        $this->tryIncrementTimePlayed();

        $arena->getScoreboardHandler()->updateTime($this->timePassedAfterOvertime, true);

        $arena->getBlockHandler()->checkLastMovements();

        if($this->timePassed % 15 === 0){
            $arena->getPowerupHandler()->dropPowerups();
        }

        $minutesSinceOvertime = $this->timePassedAfterOvertime / 60;
        $fuse = (int) (self::OVERTIME_TNT_BASE_FUSE * (self::OVERTIME_TNT_FUSE_DECAY ** $minutesSinceOvertime));

        foreach($arena->getAlivePlayers() as $player){
            // This is all copied from TNT::ignite()
            // We don't use that because we want the PrimedTNT to spawn directly
            $tnt = new PrimedTNT(Location::fromObject($player->getPosition()->add(0, 5, 0), $player->getWorld()));
            $tnt->setFuse($fuse);
            $mot = (new Random())->nextSignedFloat() * M_PI * 2;
            $tnt->setMotion(new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));

            $tnt->spawnToAll();
            $tnt->broadcastSound(new IgniteSound());
        }

        if(count($arena->getAlivePlayers()) <= 1){
            $this->finishArena();
        }
    }

    public function getPlayingTime() : int{
        return Meltdown::$PLAYING_TIME;
    }
}