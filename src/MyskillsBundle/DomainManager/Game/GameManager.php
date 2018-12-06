<?php
namespace MyskillsBundle\DomainManager\Game;

use Application\Sonata\UserBundle\Entity\User;
use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Entity\Game;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Exception\LimitUserGameException;
use MyskillsBundle\Repository\GameRepository;
use Doctrine\ORM\EntityManager;

class GameManager extends BaseDomainManager
{
    const TOKEN_PREFIX = '_game';
    const STANDART_TIMEOUT_X = 8;
    const SEARCH_CLIPS_LIMIT = 10;
    const KincaidReadingEaseLimit = 82;
    const GunningFogScoreLimit = 7;
    const SymbolsLimit = 300;
    const HARD_HINT_LEVEL = 2;
    const LOW_HARD_LEVEL = 3;
    const MEDIUM_HARD_LEVEL = 5;
    const HIGH_HARD_LEVEL = 7;
    const COUNTRIES_SHOW_LEVEL = 3;
    const GENRES_SHOW_LEVEL = 4;
    const CHOOSE_TWO_SERIES_LEVEL = 5;
    const CHOOSE_UNLIM_SERIES_LEVEL = 6;
    const TIMEOUT_X5_LEVEL = 5;
    const TIMEOUT_X2_LEVEL = 6;
    const TIMEOUT_X1_8_LEVEL = 9;
    const HIDE_HARDSUB_LEVEL = 4;
    const SHOW_HARDSUB_LEVEL = 10;
    const DOUBLE_SCORE_BY_FAVOURITE_SERIES_LEVEL = 8;
    const DOUBLE_SCORE_LEVEL = 9;
    const SUBSCRIBE_LEVEL = 10;
    const SUBSCRIPTION_YEARS = 5;
    const ADD_HINT_SCORE_LOW = 1000;
    const ADD_HINT_SCORE_MEDIUM = 5000;
    const ADD_HINT_SCORE_HIGH = 10000;
    const THREE_HINTS_LEVEL = 7;

    const GAME_LEVELS_SCORE = [
        2 => 11110,
        3 => 34000,
        4 => 59500,
        5 => 104200,
        6 => 182400,
        7 => 319100,
        8 => 559000,
        9 => 977300,
        10 => 1710000,
        11 => 999999999999999 // not exist
    ];

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var VideoClipManager */
    private $videoClipManager;

    private $webDirectory;

    public function __construct(
        GameRepository $baseRepository,
        VideoClipManager $videoClipManager,
        EntityManager $em,
        $appDirectory
    )
    {
        parent::__construct($baseRepository);
        $this->em = $em;
        $this->videoClipManager = $videoClipManager;
        $this->webDirectory = $appDirectory;
    }

    public function createGame(
        $fingerPrint,
        $hash = null,
        User $user = null,
        $noHardsub = false
    ) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();

        if ($hash) {
            $oldGame = $this->getByHash($hash);

            if ($oldGame === null || $oldGame->isPenalty()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }

            $videoClip = $oldGame->getVideoClip();
        } else {

            $oldGame = null;
            $notIds = $repository->getVideoClipIds($fingerPrint, $user);
            if (!empty($notIds)) {
                $notIds = array_map(function (array $r) {
                    return $r['id'];
                }, $notIds);
            }
            $i = 0;
            $clips = [];

            $maxEase = 100;
            $minEase = 90;

            if ($user) {
                $myLevel = $user->getLevel();

                if ($myLevel >= self::LOW_HARD_LEVEL && $myLevel < self::MEDIUM_HARD_LEVEL) {
                    $maxEase = 90;
                    $minEase = 80;
                } elseif($myLevel >= self::MEDIUM_HARD_LEVEL && $myLevel < self::HIGH_HARD_LEVEL) {
                    $maxEase = 80;
                    $minEase = 70;
                } else {
                    $maxEase = 70;
                    $minEase = 0;
                }
            }

            do {
                /**
                 * @var VideoClip $videoClip
                 */
                $videoClip = $this->videoClipManager->getRandomLongVideoClip($notIds, $minEase, $maxEase, true);

                if ($videoClip === null && $i > self::SEARCH_CLIPS_LIMIT) {
                    throw new EntityNotFoundException(VideoClip::class, $user ? $user->getId() : $fingerPrint, $user ? 'user.id' : 'fingerPrint');
                }
                if ($videoClip === null) {
                    $minEase -= 5;
                    $maxEase += 5;
                    $i++;
                    continue;
                }

                $clips = $this->getClipsByParent($videoClip);
            } while (!count($clips));
        }

        $game = new Game();
        $game->setUser($user);
        $game->setFingerPrint($fingerPrint);
        $game->setVideoClip($videoClip);
        if ($oldGame) {
            if ($user && $oldGame->getUser() && $user->getId() == $oldGame->getUser()->getId()) {
                $game->setAttemptNumber($oldGame->getAttemptNumber());
            }
            $game->setParentHash($oldGame->getHash());
            $game->setLevel($oldGame->getLevel());
            $game->setGameType($oldGame->getGameType());
        }

        $game->setWithoutHardsub($noHardsub);
        $game->preUpdateTasks();

        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    /**
     * @param $hash
     * @return Game|null
     */
    public function getByHash($hash) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        return $repository->findByHash($hash);
    }

    public function getClipsByParent(VideoClip $videoClip) {
        return $this->videoClipManager->getClipsByParent($videoClip);
    }

    public function saveGame(Game $game) {
        parent::save($game, true, true);
    }

    /**
     * @param array $results
     * @param VideoClip $firstClip
     * @param $part_number
     * @param $result
     * @param $percent
     * @return mixed
     */
    public function addGameResults(array &$results, VideoClip $firstClip, $part_number, $result, &$percent) {
        $idealText = $this->clearNotWords($firstClip->getSubSearchText());
        $myText = $this->clearNotWords($result);

        // если юзер угадал всю фразу с начала строки, пускай его фраза и длиннее нужной
        if (strpos($myText, $idealText) !== 0) {
            similar_text($idealText, $myText, $percent);
            $percent = round($percent);
            $errorsCount = levenshtein($idealText, $myText);
        } else {
            $percent = 100;
            $errorsCount = 0;
        }

        $results['percent'][(int)$part_number] = $percent;
        $results['errors'][(int)$part_number] = $errorsCount;

        return $errorsCount;
    }

    private function clearNotWords($text) {
        $newText = trim(strtolower(str_replace(["\n", "\r"], ' ', $text)));
        $newText = preg_replace('/\s{2,}/iUms', ' ', $newText);
        $newText = str_replace(['.', ',', '!', '?', '#', '&', ':', ':', '"', '-', "'"], '', $newText);

        return $newText;
    }

    /**
     * @param Game $game
     * @param $countClips
     * @param array $results
     * @param array $clips
     * @return array
     */
    public function calculateScore(Game $game, User $user = null, $countClips = 0, array $results = [], array $clips = []) {

        if ($game->isPenalty()) {
            $game->setGameFinish(new \DateTime());
            $this->saveGame($game);
            return [
                'score' => 0,
                'totalResultPercent' => 0,
                'totalResultErrors' => 0,
                'percentErrors' => 0
            ];
        }

        $totalResultPercent = round(array_sum($results['percent']) / $countClips);
        $totalResultErrors = array_sum($results['errors']);
        $score = $totalResultPercent * $game->getLevel();

        $totalSymbols = 0;
        $totalFleschKincaidReadingEase = 0.0;
        $totalGunningFogScore = 0.0;

        if (!$game->getGameFinish()) {
            /**
             * @var VideoClip $clip
             */
            foreach ($clips as $clip) {
                $totalSymbols += $clip->getSymbolsCount();
                $totalFleschKincaidReadingEase += $clip->getFleschKincaidReadingEase() ?: 100;
                $totalGunningFogScore += $clip->getGunningFogScore();
            }

            $averageFleschKincaidReadingEase = round($totalFleschKincaidReadingEase / $countClips);
            $avarageGunningFogScore = round($totalGunningFogScore / $countClips);

            $percentErrors = round(($totalResultErrors / $totalSymbols)*100);

            $deltaFlesch = self::KincaidReadingEaseLimit - $averageFleschKincaidReadingEase;
            $deltaGunning = $avarageGunningFogScore - self::GunningFogScoreLimit;

            // учитываем сложность текста
            $score += ($deltaFlesch < 0 ? 0 : abs($deltaFlesch))*10;
            $score += ($deltaGunning < 0 ? 0 : abs($deltaGunning))*100;

            // если играл без хардсаба, увеличиваем на 10% счет
            if($game->isWithoutHardsub()) {
                $score *= 1.1;
            }

            // учитываем длину текста
            $deltaSymbolsLength = $totalSymbols - self::SymbolsLimit;
            $score += ($deltaSymbolsLength < 0 ? 0 : abs($deltaSymbolsLength))*10;

            // отнимаем процент ошибок
            $score = round($score * ((100 - ($percentErrors > 100 ? 100 : $percentErrors)) / 100));

            $game->setGameFinish(new \DateTime());
            $game->setCorrectPercent($totalResultPercent);
            $game->setErrorsNumber($totalResultErrors);
            $game->setAttemptNumber($game->getAttemptNumber()+1);
            $game->setScore($score);
            $game->setSymbolsCount($totalSymbols);
            $this->saveGame($game);
        } else {
            $score = $game->getScore();
            $totalResultPercent = $game->getCorrectPercent();
            $totalResultErrors = $game->getErrorsNumber();
            $percentErrors = round(($totalResultErrors / $game->getSymbolsCount())*100);
        }

        if ($user) {
            if ($user->getLevel() >= self::DOUBLE_SCORE_LEVEL) {
                $score = $score * 2;
            }
        }
        
        return [
            'score' => $score,
            'totalResultPercent' => $totalResultPercent,
            'totalResultErrors' => $totalResultErrors,
            'percentErrors' => $percentErrors
        ];
    }

    /**
     * @param Game $game
     * @param User|null $user
     * @return int
     */
    public function getTimeoutLimit(Game $game, User $user = null) {
        $videoClip = $game->getVideoClip();
        $ratio = self::STANDART_TIMEOUT_X;
        if ($user && $user->getLevel() >= self::TIMEOUT_X5_LEVEL) {
            $ratio = 5;
        }
        if ($user && $user->getLevel() >= self::TIMEOUT_X2_LEVEL) {
            $ratio = 2;
        }
        if ($user && $user->getLevel() >= self::TIMEOUT_X1_8_LEVEL) {
            $ratio = 1.8;
        }
        return ceil(($videoClip->getFinishInSeconds() - $videoClip->getStartInSeconds()) * $ratio);
    }

    /**
     * @param Game $game
     * @return bool
     */
    public function checkForPenalty(Game $game) {
        $timeLimit = $this->getTimeoutLimit($game);
        if (!$game->isVideoWatched() &&  (new \DateTime())->getTimestamp() - $game->getGameStart()->getTimestamp() > $timeLimit*2) {
            $game->setPenalty(true);
            $this->saveGame($game);
        }
        return $game->isPenalty();
    }

    /**
     * @param User $user
     * @return array
     */
    public function getUserGamesStats(User $user) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        $stats = $repository->getUserStats($user);
        if (empty($stats)) {
            return [];
        }
        $statArr = [];

        foreach ($stats as $s) {
            $statArr[$s['status_type']] = $s['cnt'];
        }
        return $statArr;
    }

    public function getGamesByUser(User $user, $page = 1, $limit = 30) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        $games = $repository->getAllGames($user, $limit, $limit * ($page - 1));
        return $games;
    }

    public function getGamesInProgress(User $user, $page = 1, $limit = 30) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        $games = $repository->getGamesInProgress($user, $limit, $limit * ($page - 1));
        return $games;
    }

    public function getFinishedGames(User $user, $page = 1, $limit = 30) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        $games = $repository->getFinishedGames($user, false, $limit, $limit * ($page - 1));
        return $games;
    }

    public function getAbandonedGames(User $user, $page = 1, $limit = 30) {
        /**
         * @var GameRepository $repository
         */
        $repository = $this->getEntityRepository();
        $games = $repository->getFinishedGames($user, true, $limit, $limit * ($page - 1));
        return $games;
    }
    
    public function generateHint(VideoClip $clip, User $user = null, $useHint = false) {
        $subText = trim(strip_tags($clip->getSubSearchText()));

        if ($user && $useHint) {
            return $subText;
        }

        // в сложном уровне показываем только начало и конец ПРЕДЛОЖЕНИЯ
        if ($user && $user->getLevel() >= self::HARD_HINT_LEVEL) {
            $strLen = strlen($subText);
            $showLimit = max(3, ceil($strLen * .1));

            $delta = $strLen - $showLimit*2;
            $delta = $delta < 0 ? 0 : $delta;

            return substr($subText, 0, $showLimit) . str_repeat('_', $delta) . substr($subText, 0 - $showLimit);
        }

        // в легком уровне показываем только начало и конец КАЖДОГО СЛОВА
        $words = explode(' ', $subText);

        foreach ($words as &$w) {
            $strLen = strlen($w);

            if($strLen == 1) {
                continue;
            }

            $showLimit = $strLen > 4 ? 2 : ceil($strLen * .1);

            $delta = $strLen - $showLimit*2;
            $delta = $delta < 0 ? 0 : $delta;

            $w = substr($w, 0, $showLimit) . str_repeat('_', $delta) . substr($w, 0 - $showLimit);
        }

        return implode(' ', $words);
    }

    /**
     * @return VideoClip
     */
    public function getRandomClip() {
        return $this->videoClipManager->getRandomLongVideoClip([], 0, 100, true);
    }
}
