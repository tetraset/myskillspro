<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * Country
 *
 * @ORM\Table(name="book", indexes={
 * @ORM\Index(name="title", columns={"is_public"}),
 * @ORM\Index(name="isPublicDatePublish", columns={"is_public", "date_publish"}),
 * @ORM\Index(name="datePublish", columns={"date_publish"}),
 * @ORM\Index(name="author", columns={"author"}),
 * @ORM\Index(name="genre", columns={"genre"}),
 * @ORM\Index(name="level", columns={"level"}),
 * @ORM\Index(name="length", columns={"length"}),
 * @ORM\Index(name="english", columns={"english"})
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})}))
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\BookRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Book implements DomainObjectInterface
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
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Accessor(getter="getTitle")
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Accessor(getter="getDescription")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Accessor(getter="getCode")
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getAuthor")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getGenre")
     */
    private $genre;

    /**
     * elementary, intermediate, ...
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getLevel")
     */
    private $level;

    /**
     * short, long, ...
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getLength")
     */
    private $length;

    /**
     * BrE - british
     * AmE - american
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getEnglish")
     */
    private $english;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getPosterUrl")
     */
    private $posterUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     * @Accessor(getter="getContent")
     */
    private $content;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Exclude
     */
    private $isPublic = false;

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
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     * @Accessor(getter="getDatePublish")
     */
    private $datePublish;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getSource")
     */
    private $source;

    /**
     * @Exclude
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media", cascade={"persist", "remove"})
     */
    private $poster;

    public function __construct() {
        $this->timeAdd = new \DateTime();
        $this->timeUpdate = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getPoster()
    {
        return $this->poster;
    }

    /**
     * @param mixed $poster
     */
    public function setPoster($poster)
    {
        $this->poster = $poster;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
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
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * @param string $genre
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param string $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return string
     */
    public function getEnglish()
    {
        return $this->english;
    }

    /**
     * @param string $english
     */
    public function setEnglish($english)
    {
        $this->english = $english;
    }

    /**
     * @return string
     */
    public function getPosterUrl()
    {
        return !empty($this->posterUrl) && strpos($this->posterUrl, 'http') !== 0 ? STATIC_SERVER . $this->posterUrl : $this->posterUrl;
    }

    /**
     * @param string $posterUrl
     */
    public function setPosterUrl($posterUrl)
    {
        $this->posterUrl = $posterUrl;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getFormattedContent()
    {
        $content = preg_replace("/([a-z0-9\'\-]{2,})/i", "<span class='word'>$1</span>", strip_tags($this->content));
        $paragraphs = explode(PHP_EOL, $content);
        $content = implode(
            "",
            array_map(
                function($paragraph) {
                    if (empty($paragraph)) {
                        return "<br />";
                    }
                    return "<p>" . trim($paragraph) . "</p>";
                },
                $paragraphs
            )
        );

        return $content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
     * @return \DateTime
     */
    public function getDatePublish()
    {
        return $this->datePublish;
    }

    /**
     * @param \DateTime $datePublish
     */
    public function setDatePublish($datePublish)
    {
        $this->datePublish = $datePublish;
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

    public function __toString() {
        return $this->code;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
    }
}