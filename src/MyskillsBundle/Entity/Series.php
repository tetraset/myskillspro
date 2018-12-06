<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Application\Sonata\MediaBundle\Entity\Media;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\Genre;
use MyskillsBundle\Entity\Tag;
use MyskillsBundle\Entity\Country;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * Series
 *
 * @deprecated
 * @ORM\Table(name="series", indexes={
 * @ORM\Index(name="isPublic", columns={"is_public"}),
 * @ORM\Index(name="isPublicDateUpdated", columns={"is_public", "time_update"}),
 * @ORM\Index(name="datePublish", columns={"date_publish"}),
 * @ORM\Index(name="startYear", columns={"start_year"}),
 * @ORM\Index(name="enTitle", columns={"en_title"}),
 * @ORM\Index(name="type", columns={"type"})
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})})
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\SeriesRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Series implements DomainObjectInterface
{
    const SERIES_TYPES = [
        'series'=>'series',
        'movie'=>'movie'
    ];
    const MINI_DESCRIPTION_LIMIT = 120;
    const LANGUAGES = ['ru', 'en'];

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
     * @Accessor(getter="getRuTitle")
     */
    private $ruTitle;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Accessor(getter="getEnTitle")
     */
    private $enTitle;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Accessor(getter="getEnDescription")
     */
    private $enDescription;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Accessor(getter="getRuDescription")
     */
    private $ruDescription;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Exclude
     */
    private $idUser;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @Exclude
     */
    private $isPublic;

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
     * @Exclude
     */
    private $datePublish;

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
     * @Accessor(getter="getEpisodesCnt")
     */
    private $episodesCnt = 0;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Accessor(getter="getStartYear")
     */
    private $startYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Accessor(getter="getFinishYear")
     */
    private $finishYear;

    /**
     * @ORM\OneToMany(targetEntity="Video", mappedBy="series", cascade={"persist", "remove"})
     * @Exclude
     */
    protected $episodes;

    /**
     * @Exclude
     * @ORM\ManyToMany(targetEntity="Genre", cascade={"persist"})
     * @ORM\JoinTable(name="series_genres",
     *      joinColumns={@ORM\JoinColumn(name="series_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id")}
     *      )
     */
    private $genres;

    /**
     * @Exclude
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(name="series_tags",
     *      joinColumns={@ORM\JoinColumn(name="series_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @Exclude
     * @ORM\ManyToMany(targetEntity="Country", cascade={"persist"})
     * @ORM\JoinTable(name="series_countries",
     *      joinColumns={@ORM\JoinColumn(name="series_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="country_id", referencedColumnName="id")}
     *      )
     */
    private $countries;

    /**
     * @Exclude
     * @ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media", cascade={"persist", "remove"})
     */
    private $poster;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Accessor(getter="getType")
     */
    private $type = 'series';

    /**
     * Для определения очередности среди всего тайтла
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Exclude
     */
    protected $number = 1;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Exclude
     */
    private $trailerId;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=13, nullable=true)
     * @Exclude
     */
    private $complexity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    private $oroLastUpdated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Exclude
     */
    private $oroLastAdded;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Exclude
     */
    private $popularity;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=1, nullable=true)
     * @Exclude
     */
    private $rating;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Accessor(getter="getBigPosterUrl")
     */
    private $bigPosterUrl;


    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @deprecated
     * @Exclude
     */
    private $duration;

    /**
     * @return string
     */
    public function getBigPosterUrl()
    {
        return !empty($this->bigPosterUrl) && strpos($this->bigPosterUrl, 'http') !== 0 ? STATIC_SERVER . $this->bigPosterUrl : $this->bigPosterUrl;
    }

    /**
     * @param string $bigPosterUrl
     */
    public function setBigPosterUrl($bigPosterUrl)
    {
        $this->bigPosterUrl = $bigPosterUrl;
    }

    /**
     * @return int
     * @deprecated
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     * @deprecated
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
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
    public function getTrailerId()
    {
        return $this->trailerId;
    }

    /**
     * @param string $trailerId
     */
    public function setTrailerId($trailerId)
    {
        $this->trailerId = $trailerId;
    }

    /**
     * @return mixed
     */
    public function getComplexity()
    {
        return $this->complexity;
    }

    /**
     * @param mixed $complexity
     */
    public function setComplexity($complexity)
    {
        $this->complexity = $complexity;
    }

    /**
     * @return \DateTime
     */
    public function getOroLastUpdated()
    {
        return $this->oroLastUpdated;
    }

    /**
     * @param \DateTime $oroLastUpdated
     */
    public function setOroLastUpdated($oroLastUpdated)
    {
        $this->oroLastUpdated = $oroLastUpdated;
    }

    /**
     * @return \DateTime
     */
    public function getOroLastAdded()
    {
        return $this->oroLastAdded;
    }

    /**
     * @param \DateTime $oroLastAdded
     */
    public function setOroLastAdded($oroLastAdded)
    {
        $this->oroLastAdded = $oroLastAdded;
    }

    /**
     * @return int
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param int $popularity
     */
    public function setPopularity($popularity)
    {
        $this->popularity = $popularity;
    }

    /**
     * @return mixed
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
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
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @param Genre $genre
     */
    public function addGenre($genre)
    {
        $this->genres->add($genre);
    }

    /**
     * @param Tag $tag
     */
    public function addTag($tag)
    {
        $this->tags->add($tag);
    }

    /**
     * @return ArrayCollection
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * @param mixed $genres
     */
    public function setGenres($genres)
    {
        $this->genres = $genres;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return ArrayCollection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param mixed $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getSlug() {
        return $this->type;
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

    public function getGenresStr() {
        return implode(
            ', ',
            array_map(
                function(Genre $g) {
                    return $g->getEnTitle() ?: $g->getRuTitle();
                },
                $this->genres->toArray()
            )
        );
    }

    public function getTagsStr() {
        return implode(
            ', ',
            array_map(
                function(Tag $g) {
                    return $g->getEnTitle() ?: $g->getRuTitle();
                },
                $this->tags->toArray()
            )
        );
    }

    public function getCountriesStr() {
        return implode(
            ', ',
            array_map(
                function(Country $c) {
                    return $c->getEnTitle() ?: $c->getRuTitle();
                },
                $this->countries->toArray()
            )
        );
    }

    public function hasEpisode($oroId, $season, $number) {
        $episodes = $this->getEpisodes();

        /** @var Video $episode */
        foreach ($episodes as $episode) {
            if(($episode->getOroId() && $episode->getOroId() == $oroId) || ($episode->getSeason() == $season && $episode->getNumber() == $number)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getEpisodes($episodes=[])
    {
        $episodesArr = empty($episodes) ?
            $this->episodes->toArray() :
            $episodes;
        uasort(
            $episodesArr,
            function(Video $e1, Video $e2) {
                if( $e1->getSeason() > $e2->getSeason() ) {
                    return 1;
                }
                if( $e1->getSeason() < $e2->getSeason() ) {
                    return -1;
                }
                if( $e1->getNumber() == $e2->getNumber() ) {
                    return 0;
                }
                return $e1->getNumber() > $e2->getNumber() ? 1 : -1;
            }
        );
        return $episodesArr;
    }



    /**
     * @param mixed $episodes
     */
    public function setEpisodes($episodes)
    {
        $this->episodes = is_array($episodes) ? new ArrayCollection($episodes) : $episodes;
    }

    /**
     * @param Video $episode
     */
    public function addEpisode($episode)
    {
        $episode->setSeries($this);
        $this->episodes->add($episode);
    }

    /**
     * @return int
     */
    public function getStartYear()
    {
        return $this->startYear;
    }

    /**
     * @param int $startYear
     */
    public function setStartYear($startYear)
    {
        $this->startYear = $startYear;
    }

    /**
     * @return int
     */
    public function getFinishYear()
    {
        return $this->finishYear;
    }

    /**
     * @param int $finishYear
     */
    public function setFinishYear($finishYear)
    {
        $this->finishYear = $finishYear;
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
    public function getRuTitle()
    {
        return $this->ruTitle;
    }

    /**
     * @param string $ruTitle
     */
    public function setRuTitle($ruTitle)
    {
        $this->ruTitle = $ruTitle;
    }

    /**
     * @return string
     */
    public function getEnTitle()
    {
        return $this->enTitle;
    }

    /**
     * @param string $enTitle
     */
    public function setEnTitle($enTitle)
    {
        $this->enTitle = $enTitle;
    }

    /**
     * @return string
     */
    public function getEnDescription()
    {
        return $this->enDescription;
    }

    /**
     * @param string $enDescription
     */
    public function setEnDescription($enDescription)
    {
        $this->enDescription = $enDescription;
    }

    /**
     * @return string
     */
    public function getRuDescription()
    {
        return $this->ruDescription;
    }

    /**
     * @param string $ruDescription
     */
    public function setRuDescription($ruDescription)
    {
        $this->ruDescription = $ruDescription;
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
    public function getEpisodesCnt()
    {
        return $this->episodesCnt;
    }

    /**
     * @param int $episodesCnt
     */
    public function setEpisodesCnt($episodesCnt)
    {
        $this->episodesCnt = $episodesCnt;
    }

    public function __construct() {
        $this->timeAdd = new \DateTime();
        $this->timeUpdate = new \DateTime();
        $this->episodes = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->countries = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->timeUpdate = new \DateTime();
        $this->episodesCnt = count($this->getEpisodes());
    }

    public function getObjectIdentifier() {
        return $this->id;
    }

    public function __toString() {
        return $this->ruTitle ?: $this->enTitle;
    }

    /**
     * Get getCriteriaForPublicTranslations
     *
     * @return Criteria
     */
    protected function getPublicCriteria()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('isPublic', true));
        return $criteria;
    }
}