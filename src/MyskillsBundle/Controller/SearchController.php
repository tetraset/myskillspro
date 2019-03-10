<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\Search\SearchManager;
use MyskillsBundle\Entity\DictWord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(service="search.controller")
 */
class SearchController extends BaseController
{
    const SEARCH_LIMIT_ON_PAGE = 20;
    const DICTIONARY_SEARCH_LIMIT_ON_PAGE = 20;
    const VIDEOCLIPS_SEARCH_LIMIT_ON_PAGE = 5;
    const SEARCH_INDEX = 'series_en';
    const DICTIONARY_ENRU_SEARCH_INDEX = 'words_en_ru';
    const DICTIONARY_RUEN_SEARCH_INDEX = 'words_ru_en';
    const VIDEOCLIPS_INDEX = 'time_subtitles_en';
    const BOOKS_INDEX = 'books_en';

    /**
     * @Route("/search", name="search")
     */
    public function searchAction(Request $request) {
        $target = $request->query->get('target');
        /** @var SearchManager $manager */
        $manager = $this->getDomainManager();
        $manager->setSessionVisit();

        switch($target) {
            case 'dictionary':
                return $this->dictionarySearchAction($request);

        }
        return $this->searchBooks($request);
    }

    private function searchBooks(Request $request)
    {
        /** @var SearchManager $manager */
        $manager = $this->getDomainManager();
        $s = $request->query->get('s');
        $limit = $request->query->get('limit', self::SEARCH_LIMIT_ON_PAGE);
        $ajax = (bool)$request->query->get('ajax', 0);
        $page = (int)$request->query->get('p', 1);
        $skip = ($page-1)*$limit;
        $weights = [
            'title' => 100,
            'author' => 90,
            'description' => 20,
            'genre' => 10
        ];
        $result = $manager->search($s, $skip, $limit, self::BOOKS_INDEX, $weights);
        $results_arr = $result['items'];
        $total = $result['total'];
        $is_more = $result['is_more'];

        if ( $ajax ) {
            return $this->render('MyskillsBundle:Video:search_books.html.twig', array(
                'books' => $results_arr,
                'is_more' => $is_more,
                'p' => $page+1,
                's' => $s
            ));
        }

        return $this->render('MyskillsBundle:Video:search.html.twig', array(
            'books' => $results_arr,
            'count' => $total,
            'is_more' => $is_more,
            'p' => $page+1,
            's' => $s
        ));
    }

    private function dictionarySearchAction(Request $request)
    {
        /** @var SearchManager $manager */
        $manager = $this->getDomainManager();
        $s = $request->query->get('s');
        $limit = $request->query->get('limit', self::DICTIONARY_SEARCH_LIMIT_ON_PAGE);
        $ajax = (bool)$request->query->get('ajax', 0);
        $page = (int)$request->query->get('p', 1);
        $skip = ($page-1)*$limit;
        $direction = preg_match('/[а-я]/i', $s) ? 'ruen' : 'enru';
        $lang = substr($direction, 0, 2);

        $result = $manager->search($s, $skip, $limit, $lang == 'en' ? self::DICTIONARY_ENRU_SEARCH_INDEX : self::DICTIONARY_RUEN_SEARCH_INDEX);
        $results_arr = $result['items'];
        $total = $result['total'];
        $is_more = $result['is_more'];
        $i = 0;

        /**
         * @var DictWord $r
         */
        foreach($results_arr as $key=>$r) {
            if ($r->getWord() == $s) {
                if ( !$i ) break;
                unset($results_arr[$key]);
                array_unshift($results_arr, $r);
                break;
            }
            $i++;
        }

        if ( $ajax ) {
            return $this->render('MyskillsBundle:Video:search_dictionary.html.twig', array(
                'results' => $results_arr,
                'is_more' => $is_more,
                'p' => $page+1,
                's' => $s,
                'w_lang' => $lang
            ));
        }

        if ($total == 1) {
            /** @var DictWord $wordObj */
            $wordObj = current($results_arr);
            $word = $wordObj->getWord();
            return $this->redirect('/'.$lang.'/'.urldecode($word).'?s='.$s.'&target=dictionary');
        }

        return $this->render('MyskillsBundle:Video:search.html.twig', array(
            'results' => $results_arr,
            'count' => $total,
            'is_more' => $is_more,
            'p' => $page+1,
            's' => $s,
            'w_lang' => $lang
        ));
    }
}
