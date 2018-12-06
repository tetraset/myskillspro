<?php
namespace MyskillsBundle\Controller;
use JMS\Serializer\SerializerBuilder;
use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Game\GameManager;
use MyskillsBundle\DomainManager\User\UserManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Entity\Game;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Exception\LimitUserGameException;
use MyskillsBundle\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DaveChild\TextStatistics as TS;
use Application\Sonata\UserBundle\Entity\User;
/**
 * @Route(service="game.controller")
 */
class GameController extends BaseController
{
    const SCORE_PERCENT_LIMIT = 60;
    private $userManager;
    public function __construct(
        BaseDomainManager $domainManager,
        UserManager $userManager
    ) {
        parent::__construct($domainManager);
        $this->userManager = $userManager;
    }
    /**
     * Кнопка "Начать игру" и, собственно, создание игры
     * @Route("/game", name="start_game_type")
     * @Route("/game/{hash}/create", name="copy_game")
     * @Method({"GET", "POST"})
     */
    public function startGame($hash = null, Request $request)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        if ($request->request->get('start')) {
            $csrfToken = $request->request->get('csrf_token');
            $csrfPrefix = $request->request->get('csrf_prefix', GameManager::TOKEN_PREFIX);
            if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
                return new RedirectResponse('/game');
            }
            $game = null;
            $fingerPrint = $request->getClientIp() . '_' . md5($_SERVER['HTTP_USER_AGENT']);
            $noHardsub = (bool) $request->request->get('nohardsub', false);
            if ($user && $user->getLevel() >= GameManager::HIDE_HARDSUB_LEVEL && $user->getLevel() < GameManager::SHOW_HARDSUB_LEVEL) {
                $noHardsub = true;
            }
            /**
             * @var Game $game
             */
            $game = $manager->createGame($fingerPrint, $hash, $this->getUser(), $noHardsub);
            $settingsChanged = false;
            if($user) {
                if ($user->isWithoutHardsub() != $noHardsub) {
                    $user->setWithoutHardsub($noHardsub);
                    $settingsChanged = true;
                }
            }
            if ($settingsChanged) {
                $this->userManager->saveUser($user);
            }
            return new RedirectResponse('/game/' . $game->getHash());
        }
        $csrfToken = $this->getTokenizer()->setAccessToken(GameManager::TOKEN_PREFIX);
        /** @var VideoClip $videoClip */
        $videoClip = $manager->getRandomClip();
        return $this->render('MyskillsBundle:Video:index_new.html.twig', [
            'csrf_token' => $csrfToken,
            'csrf_prefix' => GameManager::TOKEN_PREFIX,
            'type' => 'game',
            'episode' => null,
            'video_link' => null,
            'video_link_full' => null,
            'export' => false,
            'indexPage' => true,
            'youtube_id' => $videoClip->getYoutubeId(),
            'thumb' => $videoClip->getThumb()
        ]);
    }
    /**
     * Подводим итоги игры
     * @Route("/game/{hash}/finish", name="finish_game")
     * @Method({"GET"})
     */
    public function finishGame($hash, Request $request)
    {
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        /**
         * @var Game $game
         */
        $game = $manager->getByHash($hash);
        $user = $this->getUser();
        if ($game === null) {
            $this->createNotFoundException("The game does not exist");
        }
        if (!$game->getGameFinish()) {
            $fingerPrint = $request->getClientIp() . '_' . md5($_SERVER['HTTP_USER_AGENT']);
            if (null !== $user && null !== $game->getUser() && $user->getId() !== $game->getUser()->getId()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
            if (null !== $user && null !== $game->getUser() && $fingerPrint !== $game->getFingerPrint()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
        }
        $countClips = 0;
        $results = [];
        $clips = [];
        if(!$game->isPenalty()) {
            $clips = $manager->getClipsByParent($game->getVideoClip());
            $countClips = count($clips);
            if (empty($game->getResults())) {
                return new RedirectResponse('/game/' . $game->getHash());
            }
            $results = unserialize($game->getResults());
            if (count($results['percent']) !== $countClips) {
                return new RedirectResponse('/game/' . $game->getHash());
            }
        }
        $scoreArr = $manager->calculateScore($game, $user, $countClips, $results, $clips);
        $newLevel = false;
        $score = $scoreArr['score'];
        if ($game->getAttemptNumber() === 1 && !$game->isFinished() && $user) {
            $user->setGames($user->getGames()+1);
            if(!$game->isPenalty()) {
                $user->setScore($user->getScore() + $score);
                $myLevel = $user->getLevel();
                $myScore = $user->getScore();
                if ($myScore >= GameManager::GAME_LEVELS_SCORE[$myLevel+1]) {
                    do {
                        $myLevel++;
                    } while($myScore >= GameManager::GAME_LEVELS_SCORE[$myLevel+1]);

                    if ($myLevel === GameManager::SUBSCRIBE_LEVEL) {
                        $user->setSubscriptionMonths(12 * GameManager::SUBSCRIPTION_YEARS);
                        $user->setSubscriptionStart(new \DateTime());
                    }
                    if ($myLevel === GameManager::THREE_HINTS_LEVEL) {
                        $user->setHints($user->getHints() + 3);
                    }

                    $user->setLevel($myLevel);
                    $newLevel = true;
                }
                if (
                    $score > GameManager::ADD_HINT_SCORE_LOW && $user->getLevel() < GameManager::LOW_HARD_LEVEL ||
                    $score > GameManager::ADD_HINT_SCORE_MEDIUM && $user->getLevel() <= GameManager::MEDIUM_HARD_LEVEL ||
                    $score > GameManager::ADD_HINT_SCORE_HIGH && $user->getLevel() > GameManager::MEDIUM_HARD_LEVEL
                ) {
                    $user->setHints($user->getHints() + 1);
                }
                $this->userManager->saveUser($user);
            }
            $game->setFinished(true);
            $manager->saveGame($game);
        }
        $csrfToken = $this->getTokenizer()->setAccessToken(GameManager::TOKEN_PREFIX);
        /**
         * @var VideoClip $clip
         */
        $clip = $game->getVideoClip();
        $gameUser = $game->getUser();
        $levelPercent = 0;
        $countries = [];
        $genres = [];
        $seriesList = [];
        if ($user) {
            $levelPercent = round( ($user->getScore() / GameManager::GAME_LEVELS_SCORE[$user->getLevel()+1]) * 100 );
        }
        return $this->render('MyskillsBundle:Video:game_finish.html.twig', [
            'csrf_token' => $csrfToken,
            'csrf_prefix' => GameManager::TOKEN_PREFIX,
            'total_percent' => $scoreArr['totalResultPercent'],
            'total_errors' => $scoreArr['totalResultErrors'],
            'percent_errors' => $scoreArr['percentErrors'],
            'score' => $score,
            'game_level' => $game->getLevel(),
            'score_percent_limit' => self::SCORE_PERCENT_LIMIT,
            'new_level' => $newLevel,
            'attempt' => $game->getAttemptNumber(),
            'penalty' => $game->isPenalty(),
            'idUser' => $gameUser ? $gameUser->getId() : null,
            'user' => $gameUser,
            'thumb' =>  $clip->getThumb(),
            'hash' => $hash,
            'level_percent' => $levelPercent,
            'countries' => $countries,
            'genres' => $genres,
            'series_list' => $seriesList
        ]);
    }
    /**
     * Начинаем или продолжаем играть в определенную игру
     * @Route("/game/{hash}", name="continue_game")
     * @Method({"GET"})
     */
    public function playGame($hash, Request $request)
    {
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        $csrfToken = $this->getTokenizer()->setAccessToken(GameManager::TOKEN_PREFIX);
        /**
         * @var Game $game
         */
        $game = $manager->getByHash($hash);
        $this->checkGame($game, $hash, $request);
        if($game->getGameFinish() || $game->isPenalty() || $manager->checkForPenalty($game)) {
            return new RedirectResponse('/game/' . $game->getHash() . '/finish');
        }
        $clips = $manager->getClipsByParent($game->getVideoClip());
        $countClips = count($clips);
        $currentClipIndex = 0;
        if (!empty($game->getResults())) {
            $results = unserialize($game->getResults());
            $currentClipIndex = count($results['percent']);
        }
        if ($currentClipIndex == $countClips) {
            return new RedirectResponse('/game/' . $game->getHash() . '/finish');
        }
        if (empty($clips[$currentClipIndex])) {
            throw new EntityNotFoundException(VideoClip::class, $currentClipIndex, 'index');
        }
        /**
         * @var VideoClip $videoClip
         */
        $videoClip = $game->getVideoClip();
        $timeLimit = $manager->getTimeoutLimit($game, $this->getUser());
        return $this->render('MyskillsBundle:Video:game.html.twig', [
            'csrf_token' => $csrfToken,
            'csrf_prefix' => GameManager::TOKEN_PREFIX,
            'episode' => $videoClip,
            'video_type' => 'video/mp4',
            'type' => 'game',
            'countClips' => $countClips,
            'youtube_id' => $videoClip->getYoutubeId(),
            'clip_index' => $currentClipIndex,
            'hash' => $game->getHash(),
            'videoWatched' => $game->isVideoWatched(),
            'timeLimit' => $timeLimit,
            'hardsub' => !$game->isWithoutHardsub(),
            'start' => $videoClip->getStartInSeconds(),
            'finish' => $videoClip->getFinishInSeconds()
        ]);
    }
    /**
     * Получение данных по определенному отрывку в игре
     * @Route("/game/{hash}/{part_number}", name="play_parts")
     * @Method({"POST"})
     */
    public function apiPlayGameClips($hash, $part_number, Request $request)
    {
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', GameManager::TOKEN_PREFIX);
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        /**
         * @var Game $game
         */
        $game = $manager->getByHash($hash);
        $this->checkGame($game, $hash, $request);
        $clips = $manager->getClipsByParent($game->getVideoClip());
        if (empty($clips[(int)$part_number])) {
            throw new EntityNotFoundException(VideoClip::class, $part_number, 'index');
        }
        // Проверка на читерство (обход таймера просмотра первого длинного ролика)
        if ($manager->checkForPenalty($game)) {
            return new JsonResponse(
                ['penalty' => 1, 'link' => '/game/' . $game->getHash() . '/finish'],
                Response::HTTP_OK
            );
        }
        /**
         * @var VideoClip $firstClip
         */
        $firstClip = $clips[(int)$part_number];
        $hint = $manager->generateHint($firstClip, $this->getUser());
        if(!$game->isVideoWatched()) {
            $game->setVideoWatched(true);
            $manager->saveGame($game);
        }
        $user = $this->getUser();
        return new JsonResponse(
            [
                'penalty' => 0,
                'hint' => $hint,
                'hash' => $firstClip->getHash(),
                'hints' => $user && $user->getHints() ? $user->getHints() : 0,
                'start' => $firstClip->getStartInSeconds(),
                'finish' => $firstClip->getFinishInSeconds()
            ],
            Response::HTTP_OK
        );
    }
    /**
     * Отправка текста юзера по каждому отрывку
     * @Route("/game/{hash}/{part_number}/result", name="result_part")
     * @Method({"POST"})
     */
    public function apiPlayGameResults($hash, $part_number, Request $request)
    {
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', GameManager::TOKEN_PREFIX);
        $result = $request->request->get('result');
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        /**
         * @var Game $game
         */
        $game = $manager->getByHash($hash);
        $this->checkGame($game, $hash, $request);
        $clips = $manager->getClipsByParent($game->getVideoClip());
        if (empty($clips[(int)$part_number])) {
            throw new EntityNotFoundException(VideoClip::class, $part_number, 'number');
        }
        $results = [];
        if (!empty($game->getResults())) {
            $results = unserialize($game->getResults());
        }
        /**
         * @var VideoClip $firstClip
         */
        $firstClip = $clips[(int)$part_number];
        $percent = 0;
        $errorsCount = $manager->addGameResults($results, $firstClip, $part_number, $result, $percent);
        $game->setResults(serialize($results));
        $manager->saveGame($game);
        $idealText = $firstClip->getSubSearchText();
        $idealText = preg_replace('/<i\.word>/i', '<i class="word">', VideoClipManager::getTextWithDictTags($idealText));
        $translateText = $firstClip->getRuSubSearchText();
        return new JsonResponse(
            [
                'correct_text' => $idealText,
                'translate' => $translateText,
                'percent' => $percent,
                'errors' => $errorsCount
            ],
            Response::HTTP_OK
        );
    }
    /**
     * Получить статистику игр определенного юзера
     * @Route("/api/games/stats", name="games_stats")
     * @Method({"GET"})
     */
    public function apiGetGameStats(Request $request) {
        $userId = $request->get('user_id');
        if($userId) {
            $user = $this->userManager->getById((int)$userId);
        } else {
            $user = $this->getUser();
        }
        if($user === null) {
            return new Response(
                '{"error":"no user with id ' . $userId . '"}',
                Response::HTTP_NOT_FOUND,
                array('content-type' => 'application/json')
            );
        }
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        $stats = $manager->getUserGamesStats($user);
        return new JsonResponse($stats);
    }
    /**
     * Получить игры определенного юзера по статусу
     * @Route("/api/games", name="all_games")
     * @Route("/api/games/{status}", name="games_by_status")
     * @Method({"GET"})
     */
    public function apiGetGames($status = null, Request $request) {
        $userId = $request->get('user_id');
        $limit = (int) $request->get('limit', 30);
        $page = (int) $request->get('page', 1);
        if($userId) {
            $user = $this->userManager->getById((int)$userId);
        } else {
            $user = $this->getUser();
        }
        if($user === null) {
            return new Response(
                '{"error":"no user with id ' . $userId . '"}',
                Response::HTTP_NOT_FOUND,
                array('content-type' => 'application/json')
            );
        }
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        switch ($status) {
            case 'progress':
                $games = $manager->getGamesInProgress($user, $page, $limit);
                break;
            case 'finished':
                $games = $manager->getFinishedGames($user, $page, $limit);
                break;
            case 'abandoned':
                $games = $manager->getAbandonedGames($user, $page, $limit);
                break;
            default:
                $games = $manager->getGamesByUser($user, $page, $limit);
                break;
        }
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($games, 'json');
        return new Response(
            $jsonContent,
            Response::HTTP_OK,
            array('content-type' => 'application/json')
        );
    }
    /**
     * Использование подсказки
     * @Route("/game/{hash}/{part_number}/hint", name="show_full_hint")
     * @Method({"POST"})
     */
    public function apiUseHint($hash, $part_number, Request $request)
    {
        /**
         * @var GameManager $manager
         */
        $manager = $this->getDomainManager();
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', GameManager::TOKEN_PREFIX);
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        $user = $this->getUser();
        if ($user === null) {
            return new Response(
                '{"error":"it is functionallity only for users"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        if (!$user->getHints()) {
            return new Response(
                '{"error":"you do not have any hints"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        /**
         * @var Game $game
         */
        $game = $manager->getByHash($hash);
        $this->checkGame($game, $hash, $request);
        $clips = $manager->getClipsByParent($game->getVideoClip());
        if (empty($clips[(int)$part_number])) {
            throw new EntityNotFoundException(VideoClip::class, $part_number, 'index');
        }
        /**
         * @var VideoClip $firstClip
         */
        $firstClip = $clips[(int)$part_number];
        $hint = $manager->generateHint($firstClip, $this->getUser(), true);
        $user->setHints($user->getHints() - 1);
        $this->userManager->saveUser($user);
        return new JsonResponse(
            ['hint' => $hint, 'hints' => $user->getHints()],
            Response::HTTP_OK
        );
    }
    /**
     * @param Game|null $game
     * @param $hash
     * @param Request $request
     */
    private function checkGame($game, $hash, Request $request) {
        try {
            if ($game === null) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
            $fingerPrint = $request->getClientIp() . '_' . md5($_SERVER['HTTP_USER_AGENT']);
            $user = $this->getUser();
            if(null !== $user && null !== $game->getUser() && $user->getId() !== $game->getUser()->getId()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
            if(null !== $user && null !== $game->getUser() && $fingerPrint !== $game->getFingerPrint()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
            if(null !== $user && null !== $game->getUser()&& $fingerPrint !== $game->getFingerPrint()) {
                throw new EntityNotFoundException(Game::class, $hash, 'hash');
            }
        } catch(EntityNotFoundException $e) {
            throw $this->createNotFoundException('The game does not exist or you are not a player');
        }
    }
    /**
     * @Route("/faq", name="faq")
     * @Method({"GET"})
     */
    public function faq() {
        return $this->render('MyskillsBundle:Video:faq_ru.html.twig');
    }
}