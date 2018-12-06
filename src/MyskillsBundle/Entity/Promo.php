<?php
namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\PromoRepository")
 * @ORM\Table(name="promo", indexes={
 *     @ORM\Index(name="timeAdd", columns={"time_add"}),
 *     @ORM\Index(name="idUserIsDeleted", columns={"id_user", "is_active"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})})
 */
class Promo implements DomainObjectInterface
{
    const PROMO_SALT = 'anisub_salt';
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $code;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isPayment = false;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idUser = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeAdd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeUpdate;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isActive = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $discountPercent = 0;

    /**
     * Promo constructor.
     */
    public function __construct()
    {
        $this->code = md5(time() . self::PROMO_SALT);
        $this->timeAdd = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return boolean
     */
    public function isIsPayment()
    {
        return $this->isPayment;
    }

    /**
     * @param boolean $isPayment
     */
    public function setIsPayment($isPayment)
    {
        $this->isPayment = $isPayment;
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
     * @return \DateTime
     */
    public function getTimeUpdate()
    {
        return $this->timeUpdate;
    }

    /**
     * @param \DateTime $timeUpdate
     */
    public function setTimeUpdate($timeUpdate)
    {
        $this->timeUpdate = $timeUpdate;
    }

    /**
     * @return boolean
     */
    public function isIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return int
     */
    public function getDiscountPercent()
    {
        return $this->discountPercent;
    }

    /**
     * @param int $discountPercent
     */
    public function setDiscountPercent($discountPercent)
    {
        $this->discountPercent = $discountPercent;
    }
}