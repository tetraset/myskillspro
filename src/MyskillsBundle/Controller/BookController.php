<?php
namespace MyskillsBundle\Controller;
use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Book\BookManager;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Service\TokenService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
/**
 * @Route(service="book.controller")
 */
class BookController extends BaseController
{
    const LIMIT_BOOKS_ON_PAGE = 72;
    const CACHE_TIMEOUT = 60 * 60; // 1 hour
    /**
     * @Route("/", name="books_mainpage2")
     */
    public function indexAction(Request $request)
    {
        /** @var BookManager $manager */
        $manager = $this->getDomainManager();
        $redirects = $manager->getRedirects();
        if (!empty($redirects)) {
            return $this->redirect($redirects[0]);
        }
        $ajax = (bool)$request->query->get('ajax', 0);
        $clearCache = (bool)$request->query->get('clear_cache', 0);
        $page = (int)$request->query->get('p', 1);
        $user = $this->getUser();
        $levels = $request->query->get('levels') ?: [];
        $genres = $request->query->get('genres') ?: [];
        $lengths = $request->query->get('lengths') ?: [];
        $key = 'main_book_myskills_page_cache_'.
               (!empty($user) ? $user->getId() : '0').
               '__'.implode('_', $levels).
               '__'.implode('_', $genres).
               '__'.implode('_', $lengths).
               '__'.$ajax.'_'.$page.'__v2';
        if (!$clearCache && ($htmlContent = $this->getCacheService()->fetch($key)) !== false) {
            return $htmlContent;
        }
        $lastData = $manager->getLastBooks(self::LIMIT_BOOKS_ON_PAGE, $page, $levels, $genres, $lengths);
        $total = $lastData['total'];
        $is_more = $total > ($page - 1) * self::LIMIT_BOOKS_ON_PAGE + self::LIMIT_BOOKS_ON_PAGE;
        if ($ajax) {
            $htmlContent = $this->render(
                'MyskillsBundle:Video:books_list.html.twig',
                array(
                    'books' => $lastData['items'],
                    'page' => $page + 1,
                    'is_more' => $is_more,
                )
            );
        } else {
            $htmlContent = $this->render(
                'MyskillsBundle:Video:index_books.html.twig',
                array(
                    'books' => $lastData['items'],
                    'is_more' => $is_more,
                    'page' => $page + 1,
                    'levels' => $manager->getAllLevels(),
                    'genres' => $manager->getAllGenres(),
                    'lengths' => $manager->getAllLengths(),
                    'indexPage' => true,
                )
            );
        }
        $this->getCacheService()->save($key, $htmlContent, self::CACHE_TIMEOUT);
        return $htmlContent;
    }
    /**
     * @Route("/book/{code}", name="book_homepage")
     */
    public function showBookAction($code)
    {
        /** @var BookManager $manager */
        $manager = $this->getDomainManager();
        $csrfToken = $this->getTokenizer()->setAccessToken();
        try {
            $book = $manager->getPublicSeriesByCode($code);
        } catch (EntityNotFoundException $e) {
            throw $this->createNotFoundException('The book does not exist');
        }
        $content = $this->render(
            'MyskillsBundle:Video:book.html.twig',
            [
                'book' => $book,
                'csrf_token' => $csrfToken,
                'csrf_prefix' => TokenService::DEFAULT_TOKEN_PREFIX,
            ]
        );
        return $content;
    }
}