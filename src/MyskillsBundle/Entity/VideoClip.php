<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="video_clip", indexes={
 *     @ORM\Index(name="isPublicShortVideoCLips", columns={"is_public", "long_clip"}),
 *     @ORM\Index(name="idVideosSbSearchText", columns={"id_video", "sub_search_text"}),
 *     @ORM\Index(name="randV", columns={"is_public", "ready_for_game", "long_clip", "flesch_kincaid_reading_ease", "rand_val"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="hash", columns={"hash"})})
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\VideoClipRepository")
 */
class VideoClip implements DomainObjectInterface
{
    const GAME_SYMBOLS_LIMIT = 6;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Accessor(getter="getId")
     */
    private $id;

    /**
     * Название youtube-ролика
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Accessor(getter="getTitle")
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Accessor(getter="getHash")
     */
    private $hash;

    /**
     * диалог $videoUrl
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     * @Exclude
     */
    private $subText;

    /**
     * искомая фраза из $shortVideoUrl
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Exclude
     */
    private $subSearchText;

    /**
     * перевод фразы
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Exclude
     */
    private $ruSubSearchText;

    /**
     * время с --> в субтитрах
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Exclude
     */
    private $timeInVtt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Exclude
     */
    private $timeAdd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Exclude
     */
    private $timeUpdate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $votesCnt = 0;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $commentsCnt = 0;

    /**
     * Время начала видео $videoUrl в $videoOrigin
     * in seconds
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $startInSeconds;

    /**
     * Время конца видео $videoUrl в $videoOrigin
     * in seconds
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $finishInSeconds;

    /**
     * @ORM\ManyToOne(targetEntity="Video")
     * @ORM\JoinColumn(name="id_video", referencedColumnName="id")
     * @Exclude
     */
    private $videoOrigin;

    /**
     * @ORM\ManyToOne(targetEntity="VideoClip")
     * @ORM\JoinColumn(name="id_parent_videoclip", referencedColumnName="id")
     * @Exclude
     */
    private $parentVideoClip;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Exclude
     */
    private $isPublic = false;

    /**
     * Кадр из $shortVideoUrl видео с $subSearchText фразой
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getThumb")
     */
    private $thumb;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $symbolsCount = 0;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Exclude
     */
    private $readyForGame = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Exclude
     */
    private $longClip = false;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", length=5, nullable=false)
     * @Exclude
     */
    private $randVal;

    /**
     * Чем меньше, тем сложнее текст
     * https://en.wikipedia.org/wiki/Flesch%E2%80%93Kincaid_readability_tests
     * @ORM\Column(type="decimal", precision=6, scale=3, nullable=false)
     * @var float
     * @Exclude
     */
    private $fleschKincaidReadingEase = 0.0;

    /**
     * Чем выше, тем сложнее текст
     * https://en.wikipedia.org/wiki/Gunning_fog_index
     * @ORM\Column(type="decimal", precision=6, scale=3, nullable=false)
     * @var float
     * @Exclude
     */
    private $gunningFogScore = 0.0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $youtubeId;

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
     * @return mixed
     */
    public function getFleschKincaidReadingEase()
    {
        return $this->fleschKincaidReadingEase;
    }

    /**
     * @param mixed $fleschKincaidReadingEase
     */
    public function setFleschKincaidReadingEase($fleschKincaidReadingEase)
    {
        $this->fleschKincaidReadingEase = $fleschKincaidReadingEase;
    }

    /**
     * @return mixed
     */
    public function getGunningFogScore()
    {
        return $this->gunningFogScore;
    }

    /**
     * @param mixed $gunningFogScore
     */
    public function setGunningFogScore($gunningFogScore)
    {
        $this->gunningFogScore = $gunningFogScore;
    }

    /**
     * @return int
     */
    public function getRandVal()
    {
        return $this->randVal;
    }

    /**
     * @return boolean
     */
    public function isLongClip()
    {
        return $this->longClip;
    }

    /**
     * @param boolean $longClip
     */
    public function setLongClip($longClip)
    {
        $this->longClip = $longClip;
    }

    /**
     * @return string
     */
    public function getTimeInVtt()
    {
        return $this->timeInVtt;
    }

    /**
     * @param string $timeInVtt
     */
    public function setTimeInVtt($timeInVtt)
    {
        $this->timeInVtt = $timeInVtt;
    }

    /**
     * @return string
     */
    public function getRuSubSearchText()
    {
        return $this->ruSubSearchText;
    }

    /**
     * @param string $ruSubSearchText
     */
    public function setRuSubSearchText($ruSubSearchText)
    {
        $this->ruSubSearchText = $ruSubSearchText;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return boolean
     */
    public function isReadyForGame()
    {
        return $this->readyForGame;
    }

    /**
     * @param boolean $readyForGame
     */
    public function setReadyForGame($readyForGame)
    {
        $this->readyForGame = $readyForGame;
    }

    public function __construct() {
        $this->timeAdd = new \DateTime();
        $this->timeUpdate = new \DateTime();
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
     * @return string
     */
    public function getSubText()
    {
        return nl2br(preg_replace('/<i\.word>/i', '<i class="word">', $this->subText));
    }

    /**
     * @param string $subText
     */
    public function setSubText($subText)
    {
        $this->subText = $subText;
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
    public function getStartInSeconds()
    {
        return $this->startInSeconds;
    }

    /**
     * @param int $startInSeconds
     */
    public function setStartInSeconds($startInSeconds)
    {
        $this->startInSeconds = $startInSeconds;
    }

    /**
     * @return int
     */
    public function getFinishInSeconds()
    {
        return $this->finishInSeconds;
    }

    /**
     * @param int $finishInSeconds
     */
    public function setFinishInSeconds($finishInSeconds)
    {
        $this->finishInSeconds = $finishInSeconds;
    }

    /**
     * @return Video
     */
    public function getVideoOrigin()
    {
        return $this->videoOrigin;
    }

    /**
     * @param mixed $videoOrigin
     */
    public function setVideoOrigin($videoOrigin)
    {
        $this->videoOrigin = $videoOrigin;
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

    /**
     * @return string
     */
    public function __toString() {
        return (string) $this->getId();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersistTasks() {
        $this->randVal = mt_rand(0, 99999);
        $this->preUpdateTasks();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
        $this->symbolsCount = strlen($this->getSubSearchText());
        $this->longClip = $this->parentVideoClip === null;

        if(null === $this->getParentVideoClip()) {
            $this->readyForGame = true;
            return;
        }
        // проверка на годность для игры
        if($this->symbolsCount < self::GAME_SYMBOLS_LIMIT) {
            $this->readyForGame = false;
            return;
        }
        if(preg_match('/[@=♪\(\)\[\]]/ismU', $this->getSubSearchText())) {
            $this->readyForGame = false;
            return;
        }
        if(preg_match('/^.+:/ismU', $this->getSubSearchText())) {
            $this->readyForGame = false;
            return;
        }
        if(stripos($this->getSubSearchText(), 'Synced &') !== false) {
            $this->readyForGame = false;
            return;
        }
        if(stripos($this->getSubSearchText(), 'Sync &') !== false) {
            $this->readyForGame = false;
            return;
        }
        $this->readyForGame = true;
    }

    /**
     * @return string
     */
    public function getSubSearchText()
    {
        return htmlspecialchars_decode($this->subSearchText, ENT_COMPAT);
    }

    /**
     * @param string $subSearchText
     */
    public function setSubSearchText($subSearchText)
    {
        $this->subSearchText = $subSearchText;
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
     * @return mixed
     */
    public function getParentVideoClip()
    {
        return $this->parentVideoClip;
    }

    /**
     * @param mixed $parentVideoClip
     */
    public function setParentVideoClip($parentVideoClip)
    {
        $this->parentVideoClip = $parentVideoClip;
    }

    /**
     * @return int
     */
    public function getSymbolsCount()
    {
        return $this->symbolsCount;
    }

    /**
     * @param int $symbolsCount
     */
    public function setSymbolsCount($symbolsCount)
    {
        $this->symbolsCount = $symbolsCount;
    }

    public function getVtt() {
        return $this->videoOrigin->getVtt();
    }
}