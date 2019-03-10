<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_result", indexes={
 *     @ORM\Index(name="userGameStart", columns={"id_user", "id_game"}),
 *     @ORM\Index(name="userVideoClipGameStart", columns={"id_user", "id_game", "id_videoclip", "time_add"}),
 * })
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\UserResultRepository")
 */
class UserResult implements DomainObjectInterface
{
    const SALT = '232salalr_';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Game")
     * @ORM\JoinColumn(name="id_game", referencedColumnName="id")
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity="VideoClip")
     * @ORM\JoinColumn(name="id_videoclip", referencedColumnName="id")
     */
    private $videoClip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $timeAdd;

    /**
     * текст из диалога юзером
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $subText;

    /**
     * Процент корректного угаданного текста
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $correctPercent = 0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $hash;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $videoWatched = false;

    public function __construct() {
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
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param mixed $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

    /**
     * @return mixed
     */
    public function getVideoClip()
    {
        return $this->videoClip;
    }

    /**
     * @param mixed $videoClip
     */
    public function setVideoClip($videoClip)
    {
        $this->videoClip = $videoClip;
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
    public function getSubText()
    {
        return $this->subText;
    }

    /**
     * @param string $subText
     */
    public function setSubText($subText)
    {
        $this->subText = $subText;
    }

    /**
     * @return int
     */
    public function getCorrectPercent()
    {
        return $this->correctPercent;
    }

    /**
     * @param int $correctPercent
     */
    public function setCorrectPercent($correctPercent)
    {
        $this->correctPercent = $correctPercent;
    }

    public function getObjectIdentifier() {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isVideoWatched()
    {
        return $this->videoWatched;
    }

    /**
     * @param boolean $videoWatched
     */
    public function setVideoWatched($videoWatched)
    {
        $this->videoWatched = $videoWatched;
    }

    /**
     * @ORM\PrePersist
     */
    public function preUpdateTasks() {
        $this->hash = $this->getGame()->getId() . '_' . md5( self::SALT . $this->getGame()->getId() . '_' . $this->getUser()->getId() . '_' . $this->getVideoClip()->getId() );
    }
}