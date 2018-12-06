<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SeriesRepository extends EntityRepository
{
    const LIMIT_VIDEO_ON_PAGE = 10;
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month

    public function findOnePublicByCode($code, \DateTime $lastDateUpdate) {
        return $this->createQueryBuilder('s')
                    ->add('select', 's')
                    ->where('s.isPublic = :isPublic')
                    ->setParameter('isPublic', true)
                    ->andWhere('s.code = :code')
                    ->setParameter('code', $code)
                    ->leftJoin('s.poster', 'p')
                    ->addSelect('p')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache(
                        true,
                        self::CACHE_TIMEOUT,
                        'en_page_for_'.$code.'_'.$lastDateUpdate->getTimestamp()
                    )
                    ->getOneOrNullResult();
    }

    public function findLastAddedSerires(\DateTime $lastDateUpdate, $countryIds = [], $genreIds = [], $types = [], $cachePart = '') {
        $qb = $this->createQueryBuilder('s')
            ->add('select', 's')
            ->leftJoin('s.poster', 'p')
            ->addSelect('p')
            ->orderBy('s.timeUpdate', 'DESC')
            ->where('s.isPublic = :isPublic')
            ->setParameter('isPublic', true);

        if(!empty($countryIds)) {
            $qb->innerJoin('s.countries', 'c', 'WITH', 'c.id IN (:countries)')
                ->setParameter('countries', $countryIds);
        }
        if(!empty($genreIds)) {
            $qb->innerJoin('s.genres', 'g', 'WITH', 'g.id IN (:genres)')
               ->setParameter('genres', $genreIds);
        }
        if(!empty($types)) {
            $qb->andWhere('s.type IN (:types)')
                ->setParameter('types', $types);
        }

        return $qb->getQuery()
            ->useQueryCache (true)
            ->useResultCache(
                true,
                self::CACHE_TIMEOUT,
                'en_main_page_'.$lastDateUpdate->getTimestamp().$cachePart
            )
            ->getResult();
    }

    /**
     * @param null $code
     * @return \DateTime
     */
    public function findLastDateUpdate($code=null) {
        $qb = $this->createQueryBuilder('s')
            ->select('s.timeUpdate')
            ->orderBy('s.timeUpdate', 'DESC')
            ->where('s.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->setMaxResults(1);

        if($code) {
            $qb->andWhere('s.code = :code')
               ->setParameter('code', $code);
        }

        $entity = $qb->getQuery()
                     ->getOneOrNullResult();

        if(is_null($entity)) {
            return new \DateTime();
        }
        return $entity['timeUpdate'];
    }

    public function findByTitle($title) {
        $qb = $this->createQueryBuilder('s')
                    ->where('upper(s.enTitle) = upper(:title)')
                    ->setParameter('title', $title);

        $entity = $qb->getQuery()
                     ->getOneOrNullResult();

        return $entity;
    }

    /**
     * @param null $search
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findSeriesForGames($search = null, $limit = 10, $offset = 0) {
        $connection = $this->getEntityManager()->getConnection();

        $searchWhere = "";
        if ($search) {
            $searchWhere = "AND s.en_title LIKE :search";
        }

        $qb = $connection->prepare(
            "
                SELECT DISTINCT s.id, s.en_title, s.poster_url
                FROM series s
                INNER JOIN video_clip v ON v.id_series = s.id
                WHERE v.long_clip = 1 AND v.ready_for_game = 1 $searchWhere
                LIMIT $offset, $limit
            "
        );
        $data = [];
        if ($searchWhere) {
            $data = ['search' => $search . '%'];
        }
        $qb->execute($data);

        $results = $qb->fetchAll();

        if (empty($results)) {
            return [];
        }

        return array_map(
            function(array $s) {
                $s['poster_url'] = STATIC_SERVER . $s['poster_url'];
                return $s;
            },
            $results
        );
    }

    /**
     * @param null $search
     * @return int
     */
    public function countSeriesForGames($search = null) {
        $connection = $this->getEntityManager()->getConnection();

        $searchWhere = "";
        if ($search) {
            $searchWhere = "AND s.en_title LIKE :search";
        }

        $qb = $connection->prepare(
            "
                SELECT COUNT(DISTINCT s.id) as cnt
                FROM series s
                INNER JOIN video_clip v ON v.id_series = s.id
                WHERE v.long_clip = 1 AND v.ready_for_game = 1 $searchWhere
            "
        );
        $data = [];
        if ($searchWhere) {
            $data = ['search' => $search . '%'];
        }
        $qb->execute($data);

        $results = $qb->fetchAll();

        if (empty($results)) {
            return 0;
        }

        return $results[0]['cnt'];
    }
}