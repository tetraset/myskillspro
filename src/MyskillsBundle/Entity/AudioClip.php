<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="audio_clip", indexes={
 *     @ORM\Index(name="id_word_jp", columns={"id_word", "is_public", "time_add"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="hash", columns={"hash"})})
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\AudioClipRepository")
 */
class AudioClip implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=120, nullable=false)
     */
    private $hash;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeAdd;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $audioUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isPublic = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeUpdate;

    /**
     * @ORM\ManyToOne(targetEntity="DictWordEn", inversedBy="audioClips", cascade={"persist"})
     * @ORM\JoinColumn(name="id_word", referencedColumnName="id_word")
     */
    protected $wordContainer;

    /**
     * @var string
     *
     * @ORM\Column(name="word", type="string", length=255, nullable=false)
     */
    protected $word;

    /**
     * AudioClip constructor.
     * @param string $hash
     * @param string $audioUrl
     * @param bool $isPublic
     * @param DictWord $wordContainer
     * @param string $word
     */
    public function __construct($hash, $audioUrl, $isPublic, DictWord $wordContainer, $word)
    {
        $this->hash = $hash;
        $this->audioUrl = $audioUrl;
        $this->isPublic = $isPublic;
        $this->wordContainer = $wordContainer;
        $this->timeAdd = new \DateTime();
        $this->timeUpdate = new \DateTime();
        $this->word = $word;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getObjectIdentifier() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }

    /**
     * @param string $word
     */
    public function setWord($word)
    {
        $this->word = $word;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
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
    public function getAudioUrl()
    {
        return strpos($this->audioUrl, '//') === false ? STATIC_SERVER . $this->audioUrl : $this->audioUrl;
    }

    /**
     * @param string $audioUrl
     */
    public function setAudioUrl($audioUrl)
    {
        $this->audioUrl = $audioUrl;
    }

    /**
     * @return boolean
     */
    public function isIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * @param boolean $isPublic
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
    }

    /**
     * @return mixed
     */
    public function getWordContainer()
    {
        return $this->wordContainer;
    }

    /**
     * @param mixed $wordContainer
     */
    public function setWordContainer($wordContainer)
    {
        $this->wordContainer = $wordContainer;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getTimeUpdate()
    {
        return $this->timeUpdate;
    }

    /**
     * @param mixed $timeUpdate
     */
    public function setTimeUpdate($timeUpdate)
    {
        $this->timeUpdate = $timeUpdate;
    }
}