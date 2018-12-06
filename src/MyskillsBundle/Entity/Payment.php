<?php
namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\PaymentRepository")
 * @ORM\Table(name="payment", indexes={
 *     @ORM\Index(name="timeAdd", columns={"time_add"})
 * })
 */
class Payment implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idUser;

    /**
     * id промо кода
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idPromo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeAdd;

    /**
     * Способ оплаты
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $operationPs;

    /**
     * Дата и время формирования операции
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $mOperationDate;

    /**
     * Дата и время выполнения платежа
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $mOperationPayDate;

    /**
     * Идентификатор платежа
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $mOrderid;

    /**
     * Сумма платежа
     * @var string
     *
     * @ORM\Column(type="decimal", nullable=false)
     */
    private $mAmount;

    /**
     * Валюта платежа
     * @var string
     *
     * @ORM\Column(type="string", length=3, nullable=false)
     */
    private $mCurr;

    /**
     * Описание платежа
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $mDesc;

    /**
     * Статус платежа
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $mStatus;

    /**
     * Кол-во месяцев подписки
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $subscriptionTerm;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $test = false;

    public function __construct(
        $idUser,
        $operationPs,
        $mOperationDate,
        $mOperationPayDate,
        $mOrderid,
        $mAmount,
        $mCurr,
        $mDesc,
        $mStatus,
        $subscriptionTerm,
        $isTest=false
    ) {
        $this->idUser = $idUser;
        $this->operationPs = $operationPs;
        $this->mOperationDate = $mOperationDate;
        $this->mOperationPayDate = $mOperationPayDate;
        $this->mOrderid = $mOrderid;
        $this->mAmount = $mAmount;
        $this->mCurr = $mCurr;
        $this->mDesc = $mDesc;
        $this->mStatus = $mStatus;
        $this->timeAdd = new \DateTime();
        $this->subscriptionTerm = $subscriptionTerm;
        $this->test = $isTest;
    }

    /**
     * @return int
     */
    public function getIdPromo()
    {
        return $this->idPromo;
    }

    /**
     * @param int $idPromo
     */
    public function setIdPromo($idPromo)
    {
        $this->idPromo = $idPromo;
    }

    /**
     * @return boolean
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param boolean $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    /**
     * @return int
     */
    public function getSubscriptionTerm()
    {
        return $this->subscriptionTerm;
    }

    /**
     * @param int $subscriptionTerm
     */
    public function setSubscriptionTerm($subscriptionTerm)
    {
        $this->subscriptionTerm = $subscriptionTerm;
    }

    public function getObjectIdentifier() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param int $idUser
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;
    }

    /**
     * @return \DateTime
     */
    public function getTimeAdd()
    {
        return $this->timeAdd;
    }

    /**
     * @param \DateTime $timeAdd
     */
    public function setTimeAdd($timeAdd)
    {
        $this->timeAdd = $timeAdd;
    }

    /**
     * @return string
     */
    public function getOperationPs()
    {
        return $this->operationPs;
    }

    /**
     * @param string $operationPs
     */
    public function setOperationPs($operationPs)
    {
        $this->operationPs = $operationPs;
    }

    /**
     * @return \DateTime
     */
    public function getMOperationDate()
    {
        return $this->mOperationDate;
    }

    /**
     * @param \DateTime $mOperationDate
     */
    public function setMOperationDate($mOperationDate)
    {
        $this->mOperationDate = $mOperationDate;
    }

    /**
     * @return \DateTime
     */
    public function getMOperationPayDate()
    {
        return $this->mOperationPayDate;
    }

    /**
     * @param \DateTime $mOperationPayDate
     */
    public function setMOperationPayDate($mOperationPayDate)
    {
        $this->mOperationPayDate = $mOperationPayDate;
    }

    /**
     * @return string
     */
    public function getMOrderid()
    {
        return $this->mOrderid;
    }

    /**
     * @param string $mOrderid
     */
    public function setMOrderid($mOrderid)
    {
        $this->mOrderid = $mOrderid;
    }

    /**
     * @return string
     */
    public function getMAmount()
    {
        return $this->mAmount;
    }

    /**
     * @param string $mAmount
     */
    public function setMAmount($mAmount)
    {
        $this->mAmount = $mAmount;
    }

    /**
     * @return string
     */
    public function getMCurr()
    {
        return $this->mCurr;
    }

    /**
     * @param string $mCurr
     */
    public function setMCurr($mCurr)
    {
        $this->mCurr = $mCurr;
    }

    /**
     * @return string
     */
    public function getMDesc()
    {
        return $this->mDesc;
    }

    /**
     * @param string $mDesc
     */
    public function setMDesc($mDesc)
    {
        $this->mDesc = $mDesc;
    }

    /**
     * @return string
     */
    public function getMStatus()
    {
        return $this->mStatus;
    }

    /**
     * @param string $mStatus
     */
    public function setMStatus($mStatus)
    {
        $this->mStatus = $mStatus;
    }
}