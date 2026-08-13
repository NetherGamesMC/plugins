<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\utils;


use DateInterval;
use DateTime;
use DateTimeZone;
use NetherGames\NGEssentials\NGEssentials;
use NetherGames\NGEssentials\ServerData;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\BaseClass;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function in_array;
use function strtoupper;

class TournamentManager extends BaseClass
{
    private bool $showTournament = false;

    public function __construct(NGEssentials $plugin)
    {
        parent::__construct($plugin);

        $plugin->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function (): void {
            $plugin = $this->getPlugin();
            $changed = false;

            if ($this->shouldShow()) {
                if (!$this->showTournament) {
                    $this->showTournament = true;
                    $changed = true;
                }
            } elseif ($this->showTournament) {
                $this->showTournament = false;
                $changed = true;
            } else {
                return;
            }

            $entityManager = $plugin->getEntityManager();
            $entityManager->updateBossBar();
            $this->updateTournamentText($changed);
        }), 30 * 20, 60 * 20);
    }

    public function shouldShow(): bool
    {
        $serverTypes = $this->getServerTypes();
        $serverTypes[] = ServerManager::LOBBY;

        $serverManager = $this->getPlugin()->getServerManager();

        return in_array($serverManager->getServerType(), $serverTypes) && $this->inTournament();
    }

    public function getBossBarContent(): string
    {
        if ($this->shouldShow()) {
            return TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . $this->getTitle() . TextFormat::EOL . TextFormat::AQUA . "Round ends in " . $this->getPrettyTimeLeft() . " - ngmc.co/tm";
        }

        return "";
    }

    public function getPrettyTimeLeft(bool $tillReset = true): string
    {
        $timeLeft = $this->getTimeLeft($tillReset);

        if ($timeLeft === null) {
            return "";
        }

        return match (true) {
            $timeLeft->d >= 1 => $timeLeft->d . " day" . ($timeLeft->d > 1 ? "s" : ""),
            $timeLeft->h >= 1 => $timeLeft->h . " hour" . ($timeLeft->h > 1 ? "s" : ""),
            $timeLeft->i >= 1 => $timeLeft->i . " minute" . ($timeLeft->i > 1 ? "s" : ""),
            default => "less than a minute"
        };
    }

    private function getData(): array
    {
        return $this->getPlugin()->getServerData()->getArray(ServerData::TOURNAMENT);
    }

    /**
     * @return string[]
     */
    public function getServerTypes(): array
    {
        return $this->getData()["serverTypes"] ?? [];
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->getData()["columns"] ?? [];
    }

    /**
     * Returns if there's a tournament going on the entire network.
     *
     * @return bool
     */
    public function inTournament(): bool
    {
        $startTime = $this->getStart();
        $endTime = $this->getEnd();

        if ($startTime === null || $endTime === null) {
            return false;
        }

        $now = new DateTime();
        return $now >= $startTime && $now <= $endTime;
    }

    public function getTitle(): string
    {
        return $this->getData()["title"] ?? "";
    }

    public function getTimeLeft(bool $tillReset = true): ?DateInterval
    {
        $endTime = $this->getEnd();

        if ($endTime === null) {
            return null;
        }

        $now = new DateTime('now');
        $endDiff = $now->diff($endTime);
        if ($tillReset) {
            $timezone = new DateTimeZone('UTC');
            $resetDate = new DateTime('tomorrow', $timezone);
            $resetDiff = $now->diff($resetDate);
            return ($resetDate < $endTime) ? $resetDiff : $endDiff;
        }

        return $endDiff;
    }

    public function getStart(): ?DateTime
    {
        return $this->getDateTime($this->getData()["startTime"] ?? null);
    }

    public function getEnd(): ?DateTime
    {
        return $this->getDateTime($this->getData()["endTime"] ?? null);
    }

    private function getDateTime(?string $timeString): ?DateTime
    {
        if ($timeString === null) {
            return null;
        }

        $unixTime = strtotime($timeString);
        return new DateTime("@$unixTime");
    }

    public function updateTournamentText(bool $changed): void
    {
        $plugin = $this->getPlugin();
        $defaultWorld = $plugin->getServer()->getWorldManager()->getDefaultWorld();

        if ($plugin->getServerManager()->getServerType() !== ServerManager::LOBBY) {
            return;
        }

        $entityId = $plugin->getServerData()->getArray(ServerData::BOARDS)[9] ?? null;
        if ($entityId === null) {
            return;
        }

        /** @var ?FloatingText $entity */
        $entity = $plugin->getEntityManager()->getEntity($defaultWorld, $entityId);
        if ($entity === null) {
            return;
        }

        $shouldShow = $this->shouldShow();
        if ($changed) {
            $entity->getMetadata()->setGenericFlag(EntityMetadataFlags::INVISIBLE, !$shouldShow);
            $entity->updateMetadata();
        }

        if (!$shouldShow) {
            return;
        }

        $title = TextFormat::BOLD . TextFormat::GREEN . strtoupper($this->getTitle()) . TextFormat::RESET . TextFormat::EOL .
            TextFormat::GOLD . 'ngmc.co/tournament' . TextFormat::EOL .
            TextFormat::GRAY . 'Round ends in ' . $this->getPrettyTimeLeft();
        $entity->setTitle($title);
        $entity->updateMetadata();
    }
}