<?php

namespace MyskillsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;

/**
 * Class Video
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\VideoRepository")
 * @ORM\Table(
 *     name="video",
 *     indexes={
 *     }, uniqueConstraints={@ORM\UniqueConstraint(name="youtube", columns={"youtube_id"})})
 * )
 */
class Video implements DomainObjectInterface
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $youtubeId;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $description;

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
    private $timePublish;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $votesCnt = 0;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $commentsCnt = 0;

    /**
     * @deprecated
     * in seconds
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thumb;

    /**
     * @ORM\OneToOne(targetEntity="VttSubtitle",cascade={"refresh", "persist", "remove"})
     * @ORM\JoinColumn(name="id_subtitle", referencedColumnName="id")
     */
    private $vtt;

    /**
     * 1 - годится для нарезки, 2 - находится в процессе нарезки, 3 - нарезано
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $cutType = 1;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $channelCode;

    /**
     * @return string
     */
    public function getChannelCode()
    {
        return $this->channelCode;
    }

    /**
     * @param string $channelCode
     */
    public function setChannelCode($channelCode)
    {
        $this->channelCode = $channelCode;
    }

    /**
     * @return VttSubtitle
     */
    public function getVtt()
    {
        return $this->vtt;
    }

    /**
     * @param mixed $vtt
     */
    public function setVtt($vtt)
    {
        $this->vtt = $vtt;
    }

    /**
     * @return int
     */
    public function getCutType()
    {
        return $this->cutType;
    }

    /**
     * @param int $cutType
     */
    public function setCutType($cutType)
    {
        $this->cutType = $cutType;
    }

    /**
     * @return string
     */
    public function getThumb()
    {
        return $this->thumb;
    }

    /**
     * @param string $thumb
     */
    public function setThumb($thumb)
    {
        $this->thumb = $thumb;
    }
    
    public function getObjectIdentifier() {
        return $this->youtubeId;
    }

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
     * @return int
     */
    public function getVotesCnt()
    {
        return $this->votesCnt;
    }

    /**
     * @param int $votesCnt
     */
    public function setVotesCnt($votesCnt)
    {
        $this->votesCnt = $votesCnt;
    }

    /**
     * @return int
     */
    public function getCommentsCnt()
    {
        return $this->commentsCnt;
    }

    /**
     * @param int $commentsCnt
     */
    public function setCommentsCnt($commentsCnt)
    {
        $this->commentsCnt = $commentsCnt;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getYoutubeId()
    {
        return $this->youtubeId;
    }

    /**
     * @param string $youtubeId
     */
    public function setYoutubeId($youtubeId)
    {
        $this->youtubeId = $youtubeId;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \DateTime
     */
    public function getTimePublish()
    {
        return $this->timePublish;
    }

    /**
     * @param \DateTime $timePublish
     */
    public function setTimePublish($timePublish)
    {
        $this->timePublish = $timePublish;
    }

    public function __toString() {
        return $this->getTitle();
    }
}
