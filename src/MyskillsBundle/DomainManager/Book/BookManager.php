<?php
namespace MyskillsBundle\DomainManager\Book;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\Book;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\BookRepository;
use Doctrine\ORM\EntityManager;

class BookManager extends BaseDomainManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        BookRepository $baseRepository,
        EntityManager $em
    )
    {
        parent::__construct($baseRepository);
        $this->em = $em;
    }

    /**
     * @param $code
     * @return Book|null
     */
    public function getByCode($code) {
        /** @var BookRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findOneByCode($code);
    }

    /**
     * @param $code
     * @return Book
     * @throws EntityNotFoundException
     */
    public function getPublicSeriesByCode($code) {
        /** @var BookRepository $bookRepository */
        $bookRepository = $this->getEntityRepository();
        $lastDateUpdate = $bookRepository->findLastDateUpdate($code);
        /** @var Book $series */
        $book = $bookRepository->findOnePublicByCode($code, $lastDateUpdate);
        if(null === $book) {
            throw new EntityNotFoundException(Book::class, $code, 'code');
        }
        return $book;
    }

    /**
     * @return array
     */
    public function getRedirects() {
        return $this->getSession()
            ->getFlashBag()
            ->get('redirect', array());
    }

    /**
     * @param $limit
     * @param $page
     * @param array $levels
     * @param array $genres
     * @param array $lengths
     * @return array
     */
    public function getLastBooks($limit, $page, $levels = [], $genres = [], $lengths = []) {
        $cachePart = '__' . implode('_', $levels) .
                     '__' . implode('_', $genres) .
                     '__' . implode('_', $lengths);

        /** @var BookRepository $bookRepository */
        $bookRepository = $this->getEntityRepository();
        $lastDateUpdate = $bookRepository->findLastDateUpdate();
        $books = $bookRepository->findLastAddedBooks($lastDateUpdate, $levels, $genres, $lengths, $cachePart);
        $total = count($books);

        $books = array_slice($books, $limit*($page-1), $limit);

        return [
            'items' => $books,
            'total' => $total
        ];
    }

    /**
     * @return array
     */
    public function getAllGenres() {
        /** @var BookRepository $bookRepository */
        $bookRepository = $this->getEntityRepository();
        return $bookRepository->findAllGenres();
    }

    /**
     * @return array
     */
    public function getAllLevels() {
        /** @var BookRepository $bookRepository */
        $bookRepository = $this->getEntityRepository();
        return $bookRepository->findAllLevels();
    }

    /**
     * @return array
     */
    public function getAllLengths() {
        /** @var BookRepository $bookRepository */
        $bookRepository = $this->getEntityRepository();
        return $bookRepository->findAllLengths();
    }
}
