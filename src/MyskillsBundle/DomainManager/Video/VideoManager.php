<?php
namespace MyskillsBundle\DomainManager\Video;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\VideoRepository;
use MyskillsBundle\Service\YandexDiskService;

class VideoManager extends BaseDomainManager
{
    private $webDirectory;

    public function __construct(
        VideoRepository $baseRepository,
        $appDirectory
    )
    {
        parent::__construct($baseRepository);
        $this->webDirectory = $appDirectory;
    }

    /**
     * @param $id
     * @return Video
     * @throws EntityNotFoundException
     */
    public function getPublicVideo($id) {
        /** @var VideoRepository $repository */
        $repository = $this->getEntityRepository();
        /** @var Video $video */
        $video = $this->getById($id);
        if(null === $video) {
            throw new EntityNotFoundException(Video::class, $id);
        }
        return $video;
    }

    /**
     * @param $id
     * @return Video|null
     */
    public function getById($id) {
        /** @var VideoRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->find($id);
    }

    /**
     * @param $youtubeId
     * @return null|object
     */
    public function getByYoutubeId($youtubeId) {
        /** @var VideoRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findOneBy(['youtubeId' => $youtubeId]);
    }

    public function saveVideo(Video $video) {
        parent::save($video, true, true);
    }

    /**
     * @return Video|null
     */
    public function getReadyForCutting() {
        /** @var VideoRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findOneBy(["cutType"=>1], ["youtubeId"=>"ASC"]);
    }
}
