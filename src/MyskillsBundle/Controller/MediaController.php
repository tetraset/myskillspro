<?php

namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Series\SeriesManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(service="main.controller")
 */
class MediaController extends BaseController
{
    const LIMIT_SERIES_ON_PAGE = 72;
    const CACHE_TIMEOUT = 60 * 60; // 1 hour
    
    public function indexAction(Request $request)
    {
        /** @var SeriesManager $manager */
        $manager = $this->getDomainManager();
        $redirects = $manager->getRedirects();

        if(!empty($redirects)) {
            return $this->redirect($redirects[0]);
        }
        $ajax = (bool)$request->query->get('ajax', 0);
        $clearCache = (bool)$request->query->get('clear_cache', 0);
        $page = (int)$request->query->get('p', 1);
        $user = $this->getUser();
        $countries = $request->query->get('countries', []);
        if(!empty($countries)) {
            $countries = array_map('intval', $countries);
        } else {
            $countries = [];
        }
        $genres = $request->query->get('genres', []);
        if(!empty($genres)) {
            $genres = array_map('intval', $genres);
        } else {
            $genres = [];
        }
        $types = $request->query->get('types', []);
        if(empty($types)) {
            $types = [];
        }

        $key = 'main_myskills_page_cache_' .
               (!empty($user) ? $user->getId() : '0' ) .
               '__' . implode('_', $countries) .
               '__' . implode('_', $genres) .
               '__' . implode('_', $types) .
               '__' . $ajax . '_' . $page .
                '__v2';

        if(!$clearCache && ($htmlContent = $this->getCacheService()->fetch($key)) !== false) {
            return $htmlContent;
        }

        $lastData = $manager->getLastSeries(self::LIMIT_SERIES_ON_PAGE, $page, $countries, $genres, $types);

        $total = $lastData['total'];
        $is_more = $total > ($page-1)*self::LIMIT_SERIES_ON_PAGE + self::LIMIT_SERIES_ON_PAGE;

        if( $ajax ) {
            $htmlContent = $this->render('MyskillsBundle:Video:series_list.html.twig', array(
                'series' => $lastData['items'],
                'page' => $page+1,
                'is_more' => $is_more
            ));
        } else {
            $htmlContent = $this->render('MyskillsBundle:Video:index.html.twig', array(
                'series' => $lastData['items'],
                'is_more' => $is_more,
                'page' => $page+1,
                'countries' => $manager->getAllCountries(),
                'genres' => $manager->getAllGenres(),
                'indexPage' => true,
                'no_index' => true
            ));
        }

        $this->getCacheService()->save($key, $htmlContent, self::CACHE_TIMEOUT);
        return $htmlContent;
    }

    /**
     * @Route("/terms", name="terms")
     */
    public function terms() {
        return $this->render('MyskillsBundle:Video:terms.html.twig');
    }

    /**
     * @Route("/licence", name="licence")
     */
    public function licence() {
        return $this->render('MyskillsBundle:Video:licence.html.twig');
    }
}
