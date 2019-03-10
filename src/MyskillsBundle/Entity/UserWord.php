<?php
namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\UserWordRepository")
 * @ORM\Table(name="user_word", indexes={
 *     @ORM\Index(name="timeAdd", columns={"time_add"}),
 *     @ORM\Index(name="folderDelete", columns={"id_user", "id_folder", "is_deleted"})
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="wordUser", columns={"id_en_word", "id_user", "id_folder"})})
 */
class UserWord implements DomainObjectInterface
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
     * @ORM\Column(type="string", length=2, nullable=false)
     */
    private $translateLang = 'ru';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idVideo;

    /**
     * time in seconds
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeOnVideo;

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
     * @ORM\ManyToOne(targetEntity="DictWordEn")
     * @ORM\JoinColumn(name="id_en_word", referencedColumnName="id_word")
     */
    private $enWord;

    /**
     * искомая фраза из $shortVideoUrl
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $subSearchText;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $idFolder = 0;

    /**
     * @ORM\ManyToOne(targetEntity="VideoClip")
     * @ORM\JoinColumn(name="id_video_clip", referencedColumnName="id")
     */
    private $videoClip;

    public function __construct(DictWordEn $enWord, $idUser, $idVideo, $timeOnVideo) {
        $this->enWord = $enWord;
        $this->idUser = $idUser;
        $this->idVideo = $idVideo;
        $this->timeOnVideo = $timeOnVideo;
        $this->timeAdd = new \DateTime();
        $this->timeUpdate = new \DateTime();
    }

    /**
     * @return boolean
     */
    public function isIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param boolean $isDeleted
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    public function __toString() {
        return $this->enWord->__toString();
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
     * @return string
     */
    public function getTranslateLang()
    {
        return $this->translateLang;
    }

    /**
     * @param string $translateLang
     */
    public function setTranslateLang($translateLang)
    {
        $this->translateLang = $translateLang;
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
     * @return int
     */
    public function getIdVideo()
    {
        return $this->idVideo;
    }

    /**
     * @param int $idVideo
     */
    public function setIdVideo($idVideo)
    {
        $this->idVideo = $idVideo;
    }

    /**
     * @return int
     */
    public function getTimeOnVideo()
    {
        return $this->timeOnVideo;
    }

    /**
     * @param int $timeOnVideo
     */
    public function setTimeOnVideo($timeOnVideo)
    {
        $this->timeOnVideo = $timeOnVideo;
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
     * @return DictWordEn
     */
    public function getEnWord()
    {
        return $this->enWord;
    }

    /**
     * @param DictWordEn $enWord
     */
    public function setEnWord(DictWordEn $enWord)
    {
        $this->enWord = $enWord;
    }

    /**
     * @return int
     */
    public function getIdFolder()
    {
        return $this->idFolder;
    }

    /**
     * @param int $idFolder
     */
    public function setIdFolder($idFolder)
    {
        $this->idFolder = $idFolder;
    }

    /**
     * @return VideoClip
     */
    public function getVideoClip()
    {
        return $this->videoClip;
    }

    /**
     * @param VideoClip $videoClip
     */
    public function setVideoClip($videoClip)
    {
        $this->videoClip = $videoClip;
    }

    /**
     * @return string
     */
    public function getSubSearchText()
    {
        return $this->subSearchText;
    }

    /**
     * @param string $subSearchText
     */
    public function setSubSearchText($subSearchText)
    {
        $this->subSearchText = $subSearchText;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
    }
}