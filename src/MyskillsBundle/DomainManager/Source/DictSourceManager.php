<?php
namespace MyskillsBundle\DomainManager\Source;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\DictSource;
use MyskillsBundle\Repository\DictSourceRepository;
use Doctrine\ORM\EntityManager;

class DictSourceManager extends BaseDomainManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        DictSourceRepository $baseRepository,
        EntityManager $em
    )
    {
        parent::__construct($baseRepository);
        $this->em = $em;
    }

    /**
     * @param $id
     * @return DictSource|null
     */
    public function getById($id) {
        /** @var DictSourceRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->find($id);
    }
}
