<?php
namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\UserFolderRepository")
 * @ORM\Table(name="user_folder", indexes={
 *     @ORM\Index(name="timeAdd", columns={"time_add"}),
 *     @ORM\Index(name="idUserIdParent", columns={"id_user", "id_parent"}),
 *     @ORM\Index(name="idUserIdParentIsDeleted", columns={"id_user", "id_parent", "is_deleted"})
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="titleUserFolder", columns={"title", "id_user", "id_parent"})})
 */
class UserFolder implements DomainObjectInterface
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
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idParent = 0;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idUser;

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDeleted = false;

    /**
     * @ORM\ManyToMany(targetEntity="UserWord")
     * @ORM\JoinTable(name="user_words_folders",
     *      joinColumns={@ORM\JoinColumn(name="word_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="folder_id", referencedColumnName="id")}
     *      )
     */
    private $userWords;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $userWordsCnt = 0;

    /**
     * UserFolder constructor.
     * @param string $title
     * @param int $idUser
     */
    public function __construct($title, $idUser)
    {
        $this->title = $title;
        $this->idUser = $idUser;
        $this->userWords = new ArrayCollection();
        $this->timeAdd = new \DateTime();
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @return bool
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    /**
     * @param array $userWords
     */
    public function setUserWords(array $userWords)
    {
        $this->userWords = new ArrayCollection($userWords);
    }

    public function addUserWord(UserWord $userWord)
    {
        $this->userWords->add($userWord);
    }

    public function removeUserWord(UserWord $userWord)
    {
        $this->userWords->remove($userWord);
    }

    /**
     * @return array
     */
    public function getUserWords()
    {
        $words = $this->userWords->toArray();
        $id = $this->id;
        return array_filter($words, function($w) use ($id){
            return !$w->isIsDeleted() && $w->getIdFolder() == $id;
        });
    }

    /**
     * Get getCriteriaForActiveWords
     *
     * @return Criteria
     */
    protected function getCriteriaForActiveWords($isActive = true)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('isDeleted', !$isActive));
        return $criteria;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
        $this->countChildren();
    }

    public function countChildren() {
        $this->userWordsCnt = count($this->getUserWords());
    }

    /**
     * @return int
     */
    public function getIdParent()
    {
        return $this->idParent;
    }

    /**
     * @param int $idParent
     */
    public function setIdParent($idParent)
    {
        $this->idParent = $idParent;
    }

    /**
     * @return int
     */
    public function getUserWordsCnt()
    {
        return $this->userWordsCnt;
    }

    /**
     * @param int $userWordsCnt
     */
    public function setUserWordsCnt($userWordsCnt)
    {
        $this->userWordsCnt = $userWordsCnt;
    }

    public function getObjectIdentifier() {
        return $this->id;
    }
}