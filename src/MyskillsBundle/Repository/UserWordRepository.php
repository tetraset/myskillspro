<?php
namespace MyskillsBundle\Repository;

use MyskillsBundle\Controller\MediaController;
use Doctrine\ORM\EntityRepository;

// юзерский словарь
class UserWordRepository extends EntityRepository
{
    /**
     * @return \DateTime
     */
    public function findLastDateUpdate($idUser) {
        $entity = $this->createQueryBuilder('s')
            ->select('s.timeUpdate')
            ->orderBy('s.timeUpdate', 'DESC')
            ->where('s.idUser = :user')
            ->setParameter('user', (int)$idUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if(is_null($entity)) {
            return new \DateTime();
        }
        return $entity['timeUpdate'];
    }

    public function findLastAddedWords($idUser, $limit, $idFolder=0, $page=1, \DateTime $lastDateUpdate) {
        return $this->createQueryBuilder('s')
            ->add('select', 's', 'w')
            ->join('s.enWord', 'w')
            ->where('s.idUser = :user AND s.idFolder = :folder AND s.isDeleted = :deleted')
            ->setParameter('user', (int)$idUser)
            ->setParameter('folder', (int)$idFolder)
            ->setParameter('deleted', false)
            ->orderBy('s.timeAdd', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->useQueryCache (true)
            ->useResultCache(
                true,
                MediaController::CACHE_TIMEOUT,
                'en_words_'.$idUser.'_'.$idFolder.'_'.$page.'_'.$limit.'_'.$lastDateUpdate->getTimestamp()
            )
            ->getResult();
    }

    public function totalWords($idUser, \DateTime $lastDateUpdate, $idFolder=null) {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.idUser = :user')
            ->andWhere('s.isDeleted = :deleted')
            ->setParameter('user', (int)$idUser)
            ->setParameter('deleted', false);

        if(isset($idFolder)) {
            $qb->andWhere('s.idFolder = :folder')
               ->setParameter('folder', (int)$idFolder);
        }

         $qb->getQuery()
            ->useQueryCache (true)
            ->useResultCache(
                true,
                MediaController::CACHE_TIMEOUT,
                'en_words_total_'.$idUser.'_'.$idFolder.'_'.$lastDateUpdate->getTimestamp()
            )
            ->getSingleScalarResult();
    }
}