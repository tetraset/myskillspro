<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;

class VideoRepository extends EntityRepository
{
    public function findVideoFileUrlsArr($limit = 30) {
        $qb = $this->createQueryBuilder('v');
        $query = $qb
            ->select(
                'v.id',
                's.youtubeId',
                'v.thumb'
            )
            ->setMaxResults($limit)
            ->orderBy('v.timePublish', 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    public function findVideoForCutting($limit = 30) {
        $qb = $this->createQueryBuilder('v');
        $query = $qb
            ->select(
                'v', 's'
            )
            ->andWhere('v.cutType = 0')
            ->setMaxResults($limit)
            ->orderBy('v.youtubeId', rand(0, 100) > 50 ? 'DESC' : 'ASC')
            ->getQuery();

        return $query->getResult();
    }
}