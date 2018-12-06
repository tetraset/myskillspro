<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\Game\GameManager;
use MyskillsBundle\DomainManager\Series\SeriesManager;
use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Service\OroroService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\UserBundle\Entity\User;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VttSubtitle;
use MyskillsBundle\Service\TokenService;

/**
 * @Route(service="series.controller")
 */
class SeriesController extends BaseController
{
    /** @var VideoManager */
    private $videoManager;

    /** @var OroroService */
    private $oroService;

    public function __construct(
        SeriesManager $domainManager,
        VideoManager $videoManager,
        OroroService $ororoService
    ) {
        parent::__construct($domainManager);
        $this->videoManager = $videoManager;
        $this->oroService = $ororoService;
    }

    /**
     * @Route("/sub/{idEpisode}/download", name="sub_download")
     * @return Response
     */
    public function subDownloadAction($idEpisode) {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if(null === $user) {
            return new Response(
                'Вам необходимо войти, чтобы скачивать субтитры'
            );
        }
        if(!$user->isActiveSubscription()) {
            return new Response(
                'Скачивать субтитры можно только подписчикам'
            );
        }
        try {
            /**
             * @var Video $video
             */
            $video = $this->videoManager->getPublicVideo((int) $idEpisode);
        } catch(EntityNotFoundException $e) {
            return new Response('Видео не существует');
        }

        if(null === $video->getVtt()) {
            return new Response('Видео не имеет субтитров');
        }

        /** @var VttSubtitle $subtitle */
        $subtitle = $video->getVtt();
        $srtFilename = str_replace('.vtt', '.srt', $subtitle->getFilePath());

        if(!file_exists($srtFilename)) {
            // srt file for subscribers
            $subFile = file_get_contents($subtitle->getFilePath());
            $subFile = trim(str_replace('WEBVTT', '', $subFile));

            $subFile = preg_replace('/(\d)\.(\d)/i', '$1,$2', $subFile);
            file_put_contents($srtFilename, $subFile);
        } else {
            $subFile = file_get_contents($srtFilename);
        }

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', mime_content_type($srtFilename));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($srtFilename) . '";');
        $response->headers->set('Content-length', filesize($srtFilename));

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent($subFile);

        return $response;
    }

//    /**
//     * @Route("/series/{code}", name="series")
//     */
//    public function doramaActionOnly($code, $seasonNum=1, $episodeNum=1, Request $request) {
//        return $this->seriesAction($code, $seasonNum, $episodeNum, $request, true);
//    }
//
//    /**
//     * @Route("/series/{code}/{seasonNum}/{episodeNum}", name="series_episode")
//     */
//    public function doramaAction($code, $seasonNum=1, $episodeNum=1, Request $request) {
//        return $this->seriesAction($code, $seasonNum, $episodeNum, $request, false);
//    }
//
//    /**
//     * @Route("/movie/{code}", name="movie")
//     * @Route("/movie/{code}/{seasonNum}/{episodeNum}", name="movie_episode")
//     */
//    public function movieAction($code, $seasonNum=1, $episodeNum=1, Request $request) {
//        return $this->seriesAction($code, $seasonNum, $episodeNum, $request);
//    }

    private function seriesAction($code, $seasonNum=1, $episodeNum=1, Request $request, $onlySeries=false)
    {
        /** @var SeriesManager $seriesManager */
        $seriesManager = $this->getDomainManager();
        $episodeNum = (int)$episodeNum;
        $csrfToken = $this->getTokenizer()->setAccessToken();

        try {
            $series = $seriesManager->getPublicSeriesByCode($code);
        } catch(EntityNotFoundException $e) {
            throw $this->createNotFoundException('The series does not exist');
        }

        $episodes = [];
        $seasons = [];

        /** @var Video $episode */
        $episode = null;
        if($series->getEpisodesCnt()) {
            $episodesList = $series->getEpisodes();
            /** @var Video $e */
            foreach($episodesList as $e) {
                $episodes[] = $e;
                $seasons[$e->getSeason()] = 1;
                if($seasonNum == $e->getSeason() && $episodeNum == $e->getNumber()) {
                    $episode = $e;
                }
            }
        }
        if($onlySeries && !empty($episodes)) {
            $episode = $episodes[0];
            $seasonNum = $episode->getSeason();
        }
        if(null === $episode) {
            throw $this->createNotFoundException('The episode does not exist');
        }
        $this->oroService->addVideoUrl($episode);
        $videoLink = $episode->getVideoLink();
        $contentType = substr($videoLink, strrpos($videoLink, '.')+1) == 'mp4' ? 'video/mp4' : 'application/x-mpegURL';

        if(!$videoLink) {
            $videoLink = $episode->getOldVideoLink();
        }
        
        $seasons = array_keys($seasons);
        sort($seasons);
        
        $this->videoManager->setThumbYandexLink($episode);
        if(empty($episode->getThumb())) {
            $episode->setThumb($series->getBigPosterUrl() ? STATIC_SERVER . $series->getBigPosterUrl() : null);
        }

        $cutType = $request->get('cutType');

        if(is_numeric($cutType)) {
            $episode->setCutType((int)$cutType);
            $this->videoManager->saveVideo($episode);
        }

        $showButton = $this->getUser() && in_array($this->getUser()->getId(), [1,2]) && $episode->getYandexDiskNumber() && $episode->getCutType() === null;

        $content = $this->render('MyskillsBundle:Video:series.html.twig', array(
            'series' => $series,
            'episodes' => $episodes,
            'seasons' => $seasons,
            'episode' => $episode,
            'csrf_token' => $csrfToken,
            'video_link' => $videoLink,
            'video_type' => $contentType,
            'season_num' => $seasonNum,
            'type' => $series->getSlug(),
            'csrf_prefix' => TokenService::DEFAULT_TOKEN_PREFIX,
            'episodesLimit' => $series->getEpisodesCnt(),
            'showCutButton' => $showButton,
            'no_index' => true
        ));

        return $content;
    }

    /**
     * @Route("/forcut", name="forcut")
     */
    public function videoForCut(Request $request) {

        $videoList = $this->videoManager->getForCutting();

        if (empty($videoList)) {
           return '';
        }

        $content =  "<ul style='list-style: none; line-height: 2'>";
        /**
         * @var Video $v
         */
        foreach ($videoList as $v) {
            $content .= "<li><a target='_blank' href='/" . $v->getSeries()->getType() . '/' . $v->getSeries()->getCode() . '/' . $v->getSeason() . '/' . $v->getNumber() . "#episode'>#" . $v->getId(). ": " . $v->getTitle() . " (" . $v->getSeries()->getEnTitle() . ")</a></li>";
        }
        $content .= "</ul>";

        return new Response($content);
    }

    /**
     * @Route("/api/series/games", name="api_game_series")
     */
    public function apiSeriesForGames(Request $request) {
        $limit = (int) $request->get('limit', 10);
        $page = (int) $request->get('page', 1);
        $search = $request->get('s');

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
         * @var SeriesManager $manager
         */
        $manager = $this->getDomainManager();
        $series = $manager->getSeriesForGames($search, $page, $limit);
        $countSeries = $manager->countSeriesForGames($search);

        return new JsonResponse(['items' => $series, 'total_count' => $countSeries]);
    }
}
