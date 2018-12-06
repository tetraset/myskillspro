<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;
use MyskillsBundle\Entity\Book;

class BookRepository extends EntityRepository
{
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month

    public function findOnePublicByCode($code, \DateTime $lastDateUpdate) {
        return $this->createQueryBuilder('b')
                    ->add('select', 'b')
                    ->where('b.isPublic = :isPublic')
                    ->setParameter('isPublic', true)
                    ->andWhere('b.code = :code')
                    ->setParameter('code', $code)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache(
                        true,
                        self::CACHE_TIMEOUT,
                        'en_book_page_for_'.$code.'_'.$lastDateUpdate->getTimestamp()
                    )
                    ->getOneOrNullResult();
    }

    /**
     * @param null $code
     * @return \DateTime
     */
    public function findLastDateUpdate($code=null) {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.timeUpdate')
                   ->orderBy('b.timeUpdate', 'DESC')
                   ->where('b.isPublic = :isPublic')
                   ->setParameter('isPublic', true)
                   ->setMaxResults(1);

        if($code) {
            $qb->andWhere('b.code = :code')
               ->setParameter('code', $code);
        }

        $entity = $qb->getQuery()
                     ->getOneOrNullResult();

        if(is_null($entity)) {
            return new \DateTime();
        }
        return $entity['timeUpdate'];
    }

    public function findLastAddedBooks(\DateTime $lastDateUpdate, $levels = [], $genres = [], $lengths = [], $cachePart = '') {
        $qb = $this->createQueryBuilder('b')
                    ->select('b.code', 'b.title', 'b.posterUrl', 'b.author')
                   ->orderBy('b.datePublish', 'DESC')
                   ->where('b.isPublic = :isPublic')
                   ->setParameter('isPublic', true);

        if(!empty($levels)) {
            $qb->andWhere('b.level IN (:levels)')
               ->setParameter('levels', $levels);
        }
        if(!empty($genres)) {
            $qb->andWhere('b.genre IN (:genres)')
               ->setParameter('genres', $genres);
        }
        if(!empty($lengths)) {
            $qb->andWhere('b.length IN (:lengths)')
               ->setParameter('lengths', $lengths);
        }

        return $qb->getQuery()
                  ->useQueryCache (true)
                  ->useResultCache(
                      true,
                      self::CACHE_TIMEOUT,
                      'en_book_main_page_'.$lastDateUpdate->getTimestamp().$cachePart
                  )
                  ->getResult();
    }

    /**
     * @return array
     */
    public function findAllGenres() {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.genre')
                   ->distinct()
                   ->orderBy('b.genre', 'ASC')
                   ->where('b.isPublic = :isPublic')
                   ->setParameter('isPublic', true);

        $entities = $qb->getQuery()
                       ->useQueryCache (true)
                        ->useResultCache(
                            true,
                            self::CACHE_TIMEOUT,
                            'en_book_genres'
                        )
                     ->getResult();

        if(empty($entities)) {
            return [];
        }
        return array_map(
            function (array $book) {
                return $book['genre'];
            },
            $entities
        );
    }

    /**
     * @return array
     */
    public function findAllLevels() {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.level')
                   ->distinct()
                   ->orderBy('b.level', 'ASC')
                   ->where('b.isPublic = :isPublic')
                   ->setParameter('isPublic', true);

        $entities = $qb->getQuery()
                       ->useQueryCache (true)
                       ->useResultCache(
                           true,
                           self::CACHE_TIMEOUT,
                           'en_book_levels'
                       )
                       ->getResult();

        if(empty($entities)) {
            return [];
        }
        return array_map(
            function (array $book) {
                return $book['level'];
            },
            $entities
        );
    }

    /**
     * @return array
     */
    public function findAllLengths() {
        $qb = $this->createQueryBuilder('b')
                   ->select('b.length')
                   ->distinct()
                   ->orderBy('b.length', 'ASC')
                   ->where('b.isPublic = :isPublic')
                   ->setParameter('isPublic', true);

        $entities = $qb->getQuery()
                       ->useQueryCache (true)
                       ->useResultCache(
                           true,
                           self::CACHE_TIMEOUT,
                           'en_book_lengths'
                       )
                       ->getResult();

        if(empty($entities)) {
            return [];
        }
        return array_map(
            function (array $book) {
                return $book['length'];
            },
            $entities
        );
    }
}