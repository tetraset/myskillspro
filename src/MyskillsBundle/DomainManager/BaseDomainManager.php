<?php

namespace MyskillsBundle\DomainManager;

use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Exception\UnexpectedTypeException;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Doctrine\ORM\EntityRepository;

abstract class BaseDomainManager
{
    /** @var EntityManager */
    protected $em;
    /** @var Logger */
    protected $logger;
    /** @var EntityRepository */
    private $entityRepository;
    /** @var SessionInterface */
    private $session;
    /** @var ValidatorInterface */
    private $validator;
    /** @var User */
    protected $user;

    public function __construct(EntityRepository $baseRepository = null)
    {
        $this->setEntityRepository($baseRepository);
    }

    /**
     * @param EntityRepository $entityRepository
     */
    public function setEntityRepository(EntityRepository $entityRepository = null)
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * @return array
     */
    public function getAll($limit = null)
    {
        return $this->getEntityRepository()->findBy([], null, $limit);
    }

    /**
     * @return EntityRepository
     * @throws UnexpectedTypeException
     */
    protected function getEntityRepository()
    {
        $this->checkClass($this->entityRepository, EntityRepository::class);

        return $this->entityRepository;
    }

    /**
     * @param $object
     * @param $class
     * @return bool
     * @throws UnexpectedTypeException
     */
    protected function checkClass($object, $class)
    {
        if(!($object instanceof $class)) {
            throw new UnexpectedTypeException($object, $class);
        }

        return true;
    }

    /**
     * @param $code
     * @return object|null
     */
    public function findOneByCode($code)
    {
        return $this->getEntityRepository()->findOneByCode($code);
    }

    /**
     * @return ValidatorInterface
     * @throws UnexpectedTypeException
     */
    protected function getValidator()
    {
        $this->checkClass($this->validator, ValidatorInterface::class);

        return $this->validator;
    }

    /**
     * Save
     *
     * @param object $entity
     * @param bool $transaction
     * @param bool $flush
     * @throws UnexpectedTypeException
     */
    protected function save($entity, $transaction = true, $flush = false)
    {
        $this->checkEntity($entity);

        if(true === $transaction) {
            $this->em->transactional(function () use ($entity) {
                $this->em->persist($entity);
            });
        } else {
            $this->em->persist($entity);
            if(true === $flush) {
                $this->em->flush($entity);
            }
        }
    }

    /**
     * Check entity
     *
     * @param $entity
     * @throws UnexpectedTypeException
     */
    protected function checkEntity($entity)
    {
        $class = $this->getEntityRepository()->getClassName();
        if(!($entity instanceof $class)) {
            throw new UnexpectedTypeException($entity, $class);
        }
    }

    // handlers for crud events

    /**
     * Get by id
     *
     * @param $id
     * @return object
     * @throws EntityNotFoundException
     */
    public function getById($id)
    {
        $entity = $this->getEntityRepository()->findOneById($id);

        if(null === $entity) {
            throw new EntityNotFoundException($this->getEntityRepository()->getClassName(), $id);
        }

        return $entity;
    }

    /**
     * @param int $id
     * @return object
     * @throws EntityNotFoundException
     */
    public function deleteEntity($id)
    {
        $entity = $this->getById((int) $id);

        $this->preDelete($entity);
        $entity->setIsDeleted(true);
        $this->save($entity);

        return $entity;
    }

    protected function preDelete($entity)
    {
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->getEntityRepository()->getClassName();
    }

    /**
     * @param EntityManager $em
     */
    public function setEm(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return EntityManager
     * @throws UnexpectedTypeException
     */
    protected function getEm()
    {
        $this->checkClass($this->em, EntityManager::class);

        return $this->em;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return SessionInterface
     * @throws UnexpectedTypeException
     */
    protected function getSession()
    {
        $this->checkClass($this->session, SessionInterface::class);

        return $this->session;
    }
}