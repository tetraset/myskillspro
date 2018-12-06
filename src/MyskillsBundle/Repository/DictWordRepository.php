<?php
namespace MyskillsBundle\Repository;

use Doctrine\ORM\EntityRepository;

class DictWordRepository extends EntityRepository
{
    public function findBy(array $criteria, array $orderBy = NULL, $limit = NULL, $offset = NULL) {
        foreach($criteria as $key=>$c) {
            if ($key == 'id') {
                $criteria['idWord'] = $c;
                unset($criteria['id']);
                break;
            }
        }
        return parent::findBy( $criteria, $orderBy, $limit, $offset );
    }
}