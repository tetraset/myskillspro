<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="game", indexes={
 *     @ORM\Index(name="userGameStart", columns={"id_user", "game_start"}),
 *     @ORM\Index(name="anonimGameStart", columns={"finger_print", "game_start"}),
 *     @ORM\Index(name="userVideoClipGameStart", columns={"id_user", "id_videoclip", "game_start"}),
 *     @ORM\Index(name="userGameFinishStart", columns={"id_user", "game_finish", "game_start"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="hash", columns={"hash"})})
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\GameRepository")
 */
class Game implements DomainObjectInterface
{
    const SALT = '23782hsdfgshdfh_';
    const DICTATION_GAME_TYPE = 0;
    const JUMBLE_GAME_TYPE = 1;
    const GAME_TYPE = [
         self::DICTATION_GAME_TYPE => 'диктант',
         self::JUMBLE_GAME_TYPE => 'джамбл',
    ];

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
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * @Exclude
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Accessor(getter="getGameStart")
     */
    private $gameStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Accessor(getter="getTimeUpdate")
     */
    private $timeUpdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Accessor(getter="getGameFinish")
     */
    private $gameFinish;

    /**
     * @ORM\ManyToOne(targetEntity="VideoClip")
     * @ORM\JoinColumn(name="id_videoclip", referencedColumnName="id")
     * @Accessor(getter="getVideoClip")
     */
    private $videoClip;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    private $level = 1;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getAttemptNumber")
     */
    private $attemptNumber = 0;

    /**
     * Очки за игру
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getScore")
     */
    private $score = 0;

    /**
     * Очки за игру
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getGameType")
     */
    private $gameType = self::DICTATION_GAME_TYPE;

    /**
     * Процент корректного угаданного текста
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getCorrectPercent")
     */
    private $correctPercent = 0;

    /**
     * Количество ошибок за игру
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getErrorsNumber")
     */
    private $errorsNumber = 0;

    /**
     * Количество символов в текстах игры
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Accessor(getter="getSymbolsCount")
     */
    private $symbolsCount = 0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Accessor(getter="getHash")
     */
    private $hash;

    /**
     * Параметр для определения просмотренности видео при высоких уровнях игры
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Accessor(getter="isVideoWatched")
     */
    private $videoWatched = false;

    /**
     * Первое длинное видео без хардсаба
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Accessor(getter="isWithoutHardsub")
     */
    private $withoutHardsub = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Exclude
     */
    private $fingerPrint;

    /**
     * @ORM\Column(name="results", type="text", nullable=true)
     * @Exclude
     * @var string
     */
    private $results;

    /**
     * Штраф за читерство
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Accessor(getter="isPenalty")
     */
    private $penalty = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Exclude
     */
    private $parentHash;

    /**
     * Игра окончена, больше наград не будет
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Accessor(getter="isFinished")
     */
    private $finished = false;

    public function __construct() {
        $this->gameStart = new \DateTime();
        $this->timeUpdate = new \DateTime();
    }

    /**
     * @return boolean
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @param boolean $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return string
     */
    public function getParentHash()
    {
        return $this->parentHash;
    }

    /**
     * @param string $parentHash
     */
    public function setParentHash($parentHash)
    {
        $this->parentHash = $parentHash;
    }

    /**
     * @return boolean
     */
    public function isPenalty()
    {
        return $this->penalty;
    }

    /**
     * @param boolean $penalty
     */
    public function setPenalty($penalty)
    {
        $this->penalty = $penalty;
    }

    /**
     * @return boolean
     */
    public function isWithoutHardsub()
    {
        return $this->withoutHardsub;
    }

    /**
     * @param boolean $withoutHardsub
     */
    public function setWithoutHardsub($withoutHardsub)
    {
        $this->withoutHardsub = $withoutHardsub;
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

    /**
     * @return int
     */
    public function getErrorsNumber()
    {
        return $this->errorsNumber;
    }

    /**
     * @param int $errorsNumber
     */
    public function setErrorsNumber($errorsNumber)
    {
        $this->errorsNumber = $errorsNumber;
    }

    /**
     * @return string
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param string $results
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * @return string
     */
    public function getFingerPrint()
    {
        return $this->fingerPrint;
    }

    /**
     * @param string $fingerPrint
     */
    public function setFingerPrint($fingerPrint)
    {
        $this->fingerPrint = $fingerPrint;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
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
     * @return \DateTime
     */
    public function getGameStart()
    {
        return $this->gameStart;
    }

    /**
     * @param \DateTime $gameStart
     */
    public function setGameStart($gameStart)
    {
        $this->gameStart = $gameStart;
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
     * @return \DateTime
     */
    public function getGameFinish()
    {
        return $this->gameFinish;
    }

    /**
     * @param \DateTime $gameFinish
     */
    public function setGameFinish($gameFinish)
    {
        $this->gameFinish = $gameFinish;
    }

    /**
     * @return VideoClip
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
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getAttemptNumber()
    {
        return $this->attemptNumber;
    }

    /**
     * @param int $attemptNumber
     */
    public function setAttemptNumber($attemptNumber)
    {
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getGameType()
    {
        return $this->gameType;
    }

    /**
     * @param int $gameType
     */
    public function setGameType($gameType)
    {
        $this->gameType = $gameType;
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

    public function getObjectIdentifier()
    {
        return $this->id;
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
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePesistTasks() {
        $this->preUpdateTasks();
        $this->hash = $this->getVideoClip()->getId() . '_' . ($this->getUser() ? $this->getUser()->getId() : md5(self::SALT . $this->getFingerPrint())) . '_' . $this->getAttemptNumber() . '_' . md5(self::SALT . '_' . $this->getVideoClip()->getId() );
    }
}