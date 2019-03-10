<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;

// юзерские папки со словами
class UserFolderRepository extends EntityRepository
{
    const CACHE_TIMEOUT = 60 * 60; // 1 hour

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

    public function findLastAddedFolders($idUser, $idFolder=0, \DateTime $lastDateUpdate) {
        return $this->createQueryBuilder('s')
            ->add('select', 's')
            ->where('s.idUser = :user AND s.idParent = :parent AND s.isDeleted = :deleted')
            ->setParameter('user', (int)$idUser)
            ->setParameter('parent', (int)$idFolder)
            ->setParameter('deleted', false)
            ->orderBy('s.timeAdd', 'DESC')
            ->getQuery()
            ->useQueryCache (true)
            ->useResultCache(
                true,
                self::CACHE_TIMEOUT,
                'en_words_'.$idUser.'_'.$idFolder.'_'.$lastDateUpdate->getTimestamp()
            )
            ->getResult();
    }

    public function totalFolders($idUser, $idFolder=0, \DateTime $lastDateUpdate) {
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.idUser = :user AND s.idParent = :parent AND s.isDeleted = :deleted')
            ->setParameter('user', (int)$idUser)
            ->setParameter('parent', (int)$idFolder)
            ->setParameter('deleted', false)
            ->getQuery()
            ->useQueryCache (true)
            ->useResultCache(
                true,
                self::CACHE_TIMEOUT,
                'en_words_total_'.$idUser.'_'.$idFolder.'_'.$lastDateUpdate->getTimestamp()
            )
            ->getSingleScalarResult();
    }
}