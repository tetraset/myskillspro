<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;

class VideoClipRepository extends EntityRepository
{
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month
    const CACHE_TIMEOUT_SHORT =  60 * 60; // 1 hour

    public function findVideoFileUrlsArr($limit = 100) {
        $qb = $this->createQueryBuilder('v');
        $query = $qb
            ->select(
                'v.id',
                'v.thumb',
                'v.longClip',
                'v.youtubeId'
            )
            ->where('v.isPublic = :isPublic')
            ->join('v.series', 's')
            ->setParameter('isPublic', true)
            ->setMaxResults($limit)
            ->orderBy('v.timePublish', 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    public function findRandomClip(
        $notIds = [],
        $minEase = 90,
        $maxEase = 100
    ) {
        $qb = $this->createQueryBuilder('v');
        $qb
            ->select('v')
            ->where('v.isPublic = :isPublic')
            ->andWhere('v.readyForGame = :readyForGame')
            ->andWhere('v.longClip = :longClip')
            ->andWhere('v.fleschKincaidReadingEase BETWEEN :ease1 AND :ease2')
            ->setParameter('isPublic', true)
            ->setParameter('readyForGame', true)
            ->setParameter('longClip', true)
            ->setParameter('ease1', $minEase)
            ->setParameter('ease2', $maxEase);

        if (!empty($notIds)) {
            $qb->andWhere('v.id NOT IN (:notIds)')
               ->setParameter('notIds', $notIds);
        }

        $query = $qb
                ->setMaxResults(100)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache(
                    true,
                    self::CACHE_TIMEOUT_SHORT,
                    'clips_random_100' .
                    '_' . $minEase .
                    '_' . $maxEase .
                    '_' . implode('_', $notIds)
                );
        
        $results = $query->getResult();
        
        if(empty($results)) {
            return null;
        }
        
        return $results[array_rand($results)];
    }

    public function deleteByVideo(Video $video)
    {
        $qb = $this->createQueryBuilder('vc')
                 ->delete()
                 ->where('vc.videoOrigin = :video')
                 ->setParameter('video', $video);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param $hash
     * @return VideoClip|null
     */
    public function findPublicByHash($hash) {
        $qb = $this->createQueryBuilder('v');
        $query = $qb
            ->select('v', 'v2')
            ->where('v.hash = :hash')
            ->andWhere('v.isPublic = :isPublic')
            ->andWhere('v.longClip = :longClip')
            ->join('v.parentVideoClip', 'v2')
            ->setParameter('hash', $hash)
            ->setParameter('isPublic', true)
            ->setParameter('longClip', false)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param VideoClip $videoClip
     * @return array
     */
    public function findByParent(VideoClip $videoClip) {
        $qb = $this->createQueryBuilder('v');
        $query = $qb
            ->select('v')
            ->where('v.parentVideoClip = :videoClip')
            ->andWhere('v.isPublic = :isPublic')
            ->andWhere('v.readyForGame = :readyForGame')
            ->setParameter('videoClip', $videoClip)
            ->setParameter('isPublic', true)
            ->setParameter('readyForGame', true)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(
                true,
                self::CACHE_TIMEOUT,
                'clips_by_parent_v2_' . $videoClip->getId()
            );

        return $query->getResult();
    }
}