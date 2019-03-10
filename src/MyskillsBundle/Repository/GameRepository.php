<?php
namespace MyskillsBundle\Repository;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use MyskillsBundle\Entity\Game;

class GameRepository extends EntityRepository
{
    public function totalGamesToday($fingerPrint, $user = null) {
        $qb = $this->createQueryBuilder('g')
                   ->select('count(g.id)');

        if ($user !== null) {
            $qb->where('g.user = :user')
               ->setParameter('user', $user);
        } else {
            $qb->where('g.fingerPrint = :finerprint')
               ->setParameter('finerprint', $fingerPrint);
        }

        $qb->andWhere('g.gameStart >= :today_start')
            ->andWhere('g.gameStart <= :today_finish')
            ->setParameter('today_start', date('Y-m-d 00:00:00'))
            ->setParameter('today_finish', date('Y-m-d 23:59:59'));

        return $qb->getQuery()
           ->getSingleScalarResult();
    }
    
    public function getVideoClipIds($fingerPrint, $user = null) {
        $qb = $this->createQueryBuilder('g')
            ->select('v.id');

        if ($user !== null) {
            $qb->where('g.user = :user')
                ->setParameter('user', $user);
        } else {
            $qb->where('g.fingerPrint = :finerprint')
                ->setParameter('finerprint', $fingerPrint);
        }

        $qb->innerJoin('g.videoClip', 'v')
           ->distinct();

        return $qb->getQuery()
            ->getResult();
    }

    public function findByHash($hash) {
        $qb = $this->createQueryBuilder('g')
            ->select('g', 'v');

        $qb->where('g.hash = :hash')
            ->setParameter('hash', $hash);

        $qb->innerJoin('g.videoClip', 'v');

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return array
     */
    public function getUserStats(User $user) {
        $connection = $this->getEntityManager()->getConnection();

        $qb = $connection->prepare(
            '
                SELECT "progress" as status_type, COUNT(*) as cnt
                FROM game
                WHERE id_user = :userId AND game_finish IS NULL
                
                UNION
                
                SELECT "finished" as status_type, COUNT(*) as cnt
                FROM game
                WHERE id_user = :userId AND game_finish IS NOT NULL AND penalty = 0
                
                UNION
                
                SELECT "abandoned" as status_type, COUNT(*) as cnt
                FROM game
                WHERE id_user = :userId AND game_finish IS NOT NULL AND penalty = 1
            '
        );
        $qb->execute(['userId' => $user->getId()]);

        return $qb->fetchAll();
    }

    public function getAllGames(User $user, $limit = 30, $offset = 0) {
        $qb = $this->createQueryBuilder('g')
                   ->select('g', 'v');

        $qb->where('g.user = :user')
           ->setParameter('user', $user);

        $qb->innerJoin('g.videoClip', 'v');
        $qb->orderBy('g.gameStart', 'DESC');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()
                  ->getResult();
    }

    public function getGamesInProgress(User $user, $limit = 30, $offset = 0) {
        $qb = $this->createQueryBuilder('g')
                   ->select('g', 'v');

        $qb->where('g.user = :user')
            ->andWhere('g.gameFinish IS NULL')
            ->setParameter('user', $user);

        $qb->innerJoin('g.videoClip', 'v');
        $qb->orderBy('g.gameStart', 'DESC');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()
            ->getResult();
    }

    public function getFinishedGames(User $user, $abandoned = false, $limit = 30, $offset = 0) {
        $qb = $this->createQueryBuilder('g')
                   ->select('g', 'v');

        $qb->where('g.user = :user')
           ->andWhere('g.gameFinish IS NOT NULL')
           ->andWhere('g.penalty = :penalty')
           ->setParameter('penalty', (bool) $abandoned)
           ->setParameter('user', $user);

        $qb->innerJoin('g.videoClip', 'v');
        $qb->orderBy('g.gameStart', 'DESC');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()
                  ->getResult();
    }
}