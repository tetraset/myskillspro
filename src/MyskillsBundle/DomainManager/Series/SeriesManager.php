<?php
namespace MyskillsBundle\DomainManager\Series;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\Country;
use MyskillsBundle\Entity\Genre;
use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Tag;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\CountryRepository;
use MyskillsBundle\Repository\GenreRepository;
use MyskillsBundle\Repository\SeriesRepository;
use MyskillsBundle\Repository\TagRepository;
use Doctrine\ORM\EntityRepository;

class SeriesManager extends BaseDomainManager
{
    private $tagRepository;

    private $genreRepository;

    private $countryRepository;

    public function __construct(
        EntityRepository $baseRepository,
        TagRepository $tagRepository,
        GenreRepository $genreRepository,
        CountryRepository $countryRepository
    ) {
        parent::__construct($baseRepository);
        $this->tagRepository = $tagRepository;
        $this->genreRepository = $genreRepository;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @return array
     */
    public function getRedirects() {
        return $this->getSession()
                    ->getFlashBag()
                    ->get('redirect', array());
    }

    public function getEnGenre($genre) {
        return $this->genreRepository->findOneByEnTitle($genre);
    }

    public function getEnCountry($country) {
        return $this->countryRepository->findOneByEnTitle($country);
    }

    /**
     * @param $code
     * @return Series|null
     */
    public function getSeriesByCode($code) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->findOneByCode(trim($code));
    }

    /**
     * @param $title
     * @return Series|null
     */
    public function getSeriesByEnTitle($title) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->findByTitle(trim($title));
    }

    /**
     * @param $code
     * @return Series
     * @throws EntityNotFoundException
     */
    public function getPublicSeriesByCode($code) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        $lastDateUpdate = $seriesRepository->findLastDateUpdate($code);
        /** @var Series $series */
        $series = $seriesRepository->findOnePublicByCode($code, $lastDateUpdate);
        if(null === $series) {
            throw new EntityNotFoundException(Series::class, $code, 'code');
        }
        return $series;
    }

    /**
     * @param $limit
     * @param $page
     * @param array $countryIds
     * @param array $genreIds
     * @return array
     */
    public function getLastSeries($limit, $page, $countryIds = [], $genreIds = [], $types = []) {
        $cachePart = '__' . implode('_', $countryIds) .
                     '__' . implode('_', $genreIds) .
                     '__' . implode('_', $types);

        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        $lastDateUpdate = $seriesRepository->findLastDateUpdate();
        $series = $seriesRepository->findLastAddedSerires($lastDateUpdate, $countryIds, $genreIds, $types, $cachePart);
        $total = count($series);

        $series = array_slice($series, $limit*($page-1), $limit);

        return [
            'items' => $series,
            'total' => $total
        ];
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findPublicAll($offset, $limit) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->findBy(array('isPublic'=>true), null, $limit, $offset);
    }

    /**
     * @return array
     */
    public function getAllTags() {
        $tags = $this->tagRepository->findAll();
        if(empty($tags)) {
            return [];
        }
        return array_map(
            function(Tag $tag) {
                return ['id' => $tag->getId(), 'title' => $tag->getRuTitle() ?: $tag->getEnTitle()];
            },
            $tags
        );
    }

    /**
     * @return array
     */
    public function getAllGenres() {
        $genres = $this->genreRepository->findAll();
        if(empty($genres)) {
            return [];
        }
        return array_map(
            function(Genre $genre) {
                return ['id' => $genre->getId(), 'title' => $genre->getRuTitle() ?: $genre->getEnTitle()];
            },
            $genres
        );
    }

    /**
     * @return array
     */
    public function getAllCountries() {
        $countries = $this->countryRepository->findAll();
        if(empty($countries)) {
            return [];
        }
        return array_map(
            function(Country $country) {
                return ['id' => $country->getId(), 'title' => $country->getRuTitle() ?: $country->getEnTitle()];
            },
            $countries
        );
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getSeriesByIds($ids = []) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->findById($ids);
    }

    /**
     * @param null $search
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getSeriesForGames($search = null, $page = 1, $limit = 10) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->findSeriesForGames($search, $limit, $limit * ($page - 1));
    }

    /**
     * @param null $search
     * @return int
     */
    public function countSeriesForGames($search = null) {
        /** @var SeriesRepository $seriesRepository */
        $seriesRepository = $this->getEntityRepository();
        return $seriesRepository->countSeriesForGames($search);
    }
}
