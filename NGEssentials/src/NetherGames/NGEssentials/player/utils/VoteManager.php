<?php

declare(strict_types=1);


namespace NetherGames\NGEssentials\player\utils;


use libasynCurl\Curl;
use NetherGames\NGEssentials\player\PlayerData;
use NetherGames\NGEssentials\player\Translator;
use NetherGames\NGEssentials\ServerManager;
use NetherGames\NGEssentials\utils\CustomIcon;
use NetherGames\NGEssentials\utils\MySQLCredentials;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\player\Player;
use pocketmine\utils\InternetRequestResult;
use pocketmine\utils\TextFormat;
use function is_numeric;
use function str_replace;
use function time;

class VoteManager extends PlayerBaseClass
{
    /** @var string */
    private static string $voteKey;

    public static function setVoteKey(string $voteKey): void
    {
        self::$voteKey = $voteKey;
    }

    public function checkVote(Player $player, bool $send = false): void
    {
        /** @var NGPlayer $player */
        $playerData = $this->getPlugin()->getPlayerData();

        if ($playerData->getBool($player, PlayerData::VOTE_CHECK)) {
            Translator::sendMessage($player, "command.vote.slowdown", Translator::TYPE_ERROR);
        } else {
            $playerData->setValue($player, PlayerData::VOTE_CHECK, true);

            $page = 'https://minecraftpocket-servers.com/api/?object=votes&element=claim&key=' . self::$voteKey . '&username=' . str_replace(' ', '%20', $player->getName());
            Curl::getRequest($page, 10, [], function (?InternetRequestResult $result) use ($player, $send): void {
                if (!$player->isConnected()) {
                    return;
                }

                if ($result !== null && $result->getCode() === 200 && is_numeric($body = $result->getBody())) {
                    $return = (int)$body;

                    if ($return === 1) {
                        $serverType = ($serverManager = $this->getPlugin()->getServerManager())->getServerType();
                        $playerData = $this->getPlugin()->getPlayerData();
                        $rankManager = $this->getManager()->getRankManager();

                        $xp = $playerData->addInt($player, PlayerData::XP, 100);
                        $credits = $playerData->addInt($player, PlayerData::STATUS_CREDITS, 2);
                        $rankManager->updatePermissions($player);

                        if ($serverManager->enableLobbyHandling()) {
                            $scoreboard = $this->getPlugin()->getServerData()->getScoreBoard();
                            $scoreboard->setLine([$player], 7, CustomIcon::MYSTIC_CHEST . "Credits: " . TextFormat::GREEN . TextFormat::GREEN . $credits);
                            $scoreboard->setLine([$player], 8, CustomIcon::EXPERIENCE . "Level: " . $this->getManager()->getLevelFormat((int)ExperienceUtils::getLevelFromXp($xp)));

                            if ($serverType !== ServerManager::CREATIVE) {
                                $player->getXpManager()->setCurrentTotalXp($xp);
                            }

                            $rankManager->updateNameTag($player);
                        }

                        $playerData->setValue($player, PlayerData::VOTE_TIME, time(), true);

                        if (MySQLCredentials::isDatabaseOnline()) {
                            $page = 'https://minecraftpocket-servers.com/api/?action=post&object=votes&element=claim&key=' . self::$voteKey . '&username=' . str_replace(' ', '%20', $player->getName());
                            Curl::postRequest($page, '');
                        }
                    }

                    if ($send || $return === 1) {
                        $player->sendMessage(match ($return) {
                            0 => Translator::getTranslationPlayer($player, "vote.notyet"),
                            1 => Translator::getTranslationPlayer($player, "vote.successful", Translator::TYPE_SUCCESS),
                            2 => Translator::getTranslationPlayer($player, "vote.claimed", Translator::TYPE_ERROR),
                            default => Translator::getTranslationPlayer($player, "vote.error", Translator::TYPE_ERROR)

                        });
                    }
                } elseif ($send) {
                    Translator::sendMessage($player, "vote.error", Translator::TYPE_ERROR);
                }

                $this->getPlugin()->getPlayerData()->setValue($player, PlayerData::VOTE_CHECK, false);
            });
        }
    }
}