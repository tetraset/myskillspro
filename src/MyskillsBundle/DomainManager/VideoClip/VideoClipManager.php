<?php
namespace MyskillsBundle\DomainManager\VideoClip;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\UserWord;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\UserWordRepository;
use MyskillsBundle\Repository\VideoClipRepository;
use Doctrine\ORM\EntityManager;
use DaveChild\TextStatistics as TS;

class VideoClipManager extends BaseDomainManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    private $userWordRepository;

    private $webDirectory;

    private $textStatistics;

    public function __construct(
        VideoClipRepository $baseRepository,
        UserWordRepository $userWordRepository,
        EntityManager $em,
        $appDirectory
    )
    {
        parent::__construct($baseRepository);
        $this->em = $em;
        $this->userWordRepository = $userWordRepository;
        $this->webDirectory = $appDirectory;
        $this->textStatistics = new TS\TextStatistics;
    }

    /**
     * @param $hash
     * @return VideoClip
     * @throws EntityNotFoundException
     */
    public function getPublicByHash($hash) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        /** @var VideoClip $videoClip */
        $videoClip = $repository->findPublicByHash($hash);
        if(null === $videoClip) {
            throw new EntityNotFoundException(VideoClip::class, $hash, 'hash');
        }
        return $this->manageForView($videoClip);
    }

    /**
     * @param $hash
     * @return VideoClip|null
     * @throws EntityNotFoundException
     */
    public function getByHash($hash, $with404 = true) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        /** @var VideoClip $videoClip */
        $videoClip = $repository->findOneBy(['hash' => $hash]);
        if(null === $videoClip && !$with404) {
            return null;
        }
        if(null === $videoClip && $with404) {
            throw new EntityNotFoundException(VideoClip::class, $hash, 'hash');
        }
        return $this->manageForView($videoClip);
    }

    /**
     * @deprecated
     * @param VideoClip $videoClip
     * @return VideoClip
     */
    public function manageForView(VideoClip $videoClip) {
        $vtt = $videoClip->getSubText();
        $searchSub = $videoClip->getSubSearchText();
        $vttArr = explode(PHP_EOL, $vtt);
        $searchSubArr = strpos($searchSub, PHP_EOL) !== false ? explode(PHP_EOL, $searchSub) : [$searchSub];
        $searchSub = "";

        foreach($searchSubArr as &$subSearch) {
            foreach($vttArr as &$vttEl) {
                if(trim(strip_tags($vttEl)) == trim(strip_tags($subSearch))) {
                    $searchSub .= PHP_EOL . $vttEl;
                    $vttEl = '<p class="active_sub">' . self::getTextWithDictTags($vttEl) . '</p>';
                }
            }
        }

        foreach($vttArr as &$vttEl) {
            if(strpos($vttEl, '<p class="active_sub">') === false) {
                $vttEl = self::getTextWithDictTags($vttEl);
            }
        }

        $searchSub = strip_tags(trim($searchSub));
        $searchSub = $searchSub ?: trim(strip_tags($videoClip->getSubSearchText()));

        $videoClip->setSubSearchText(trim($searchSub));
        $videoClip->setSubText(implode(PHP_EOL, $vttArr));
        return $videoClip;
    }

    public static function getTextWithDictTags($sentence) {
        $words = explode(' ', strip_tags($sentence));
        $newSentence = '';
        if (!empty($words)) {
            foreach ($words as &$w) {
                if(strlen($w) > 2) {
                    $w = preg_replace("/([a-z0-9\'\-]{2,})/i", "<i class=\"word\">$1</i>", $w);
                }
            }
            $newSentence = implode(' ', $words);
        }

        return $newSentence;
    }

    /**
     * @param Video $video
     * @return array
     */
    public function findByVideoOrigin(Video $video) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findByVideoOrigin($video);
    }

    /**
     * @param Video $video
     * @param $start
     * @param $finish
     * @param $youtubeId
     * @param $parentVideoClip
     * @param $subSearchText
     * @param $subText
     * @param $frame
     * @param null $hashClip
     * @param null $subRuSearchText
     * @param null $timeInSubtitles
     * @return VideoClip
     */
    public function createClip(Video $video, $start, $finish, $youtubeId, $parentVideoClip, $subSearchText, $subText, $frame, $hashClip = null, $subRuSearchText = null, $timeInSubtitles = null) {

        $subSearchText = strip_tags(trim($subSearchText));
        $subSearchText = trim(str_replace(["\n", "\r"], ' ', $subSearchText));
        $subSearchText = preg_replace('/\s{2,}/iUms', ' ', $subSearchText);

        $subRuSearchText = strip_tags(trim($subRuSearchText));
        $subRuSearchText = trim(str_replace(["\n", "\r"], ' ', $subRuSearchText));
        $subRuSearchText = preg_replace('/\s{2,}/iUms', ' ', $subRuSearchText);

        $videoClip = new VideoClip();
        $videoClip->setStartInSeconds($start);
        $videoClip->setFinishInSeconds($finish);
        $videoClip->setIsPublic(true);
        $videoClip->setSubSearchText($subSearchText);
        $videoClip->setRuSubSearchText($subRuSearchText);
        $videoClip->setParentVideoClip($parentVideoClip);
        $videoClip->setSubText($subText);
        $videoClip->setThumb($frame);
        $videoClip->setVideoOrigin($video);
        $videoClip->setYoutubeId($youtubeId);
        $videoClip->setTimeInVtt($timeInSubtitles);
        $videoClip->setTitle($video->getTitle());
        $videoClip->setHash($hashClip);

        $text = $subSearchText ? $subSearchText : strip_tags(trim($subText));

        $videoClip->setFleschKincaidReadingEase($this->textStatistics->fleschKincaidReadingEase($text));
        $videoClip->setGunningFogScore($this->textStatistics->gunningFogScore($text));

        $this->em->persist($videoClip);
        return $videoClip;
    }

    /**
     * @deprecated
     * Наполнение слов юзеров видео клипами
     * @param Video $video
     */
    public function addVideoClipsToUserWords(Video $video) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        $videoClips = $repository->findByVideoOrigin($video);
        if(empty($videoClips)) {
            return;
        }
        foreach ($videoClips as $videoClip) {
            $searchText = $videoClip->getSubSearchText();
            $subSearchText = str_replace("\r\n", PHP_EOL, $searchText);
            $userWords = $this->userWordRepository->findBy(['idVideo' => $video->getId(), 'subSearchText' => $subSearchText]);
            if (empty($userWords)) {
                continue;
            }
            /** @var UserWord $userWord */
            foreach ($userWords as $userWord) {
                $userWord->setVideoClip($videoClip);
            }
        }
        $this->em->flush();
    }

    /**
     * @param Video $video
     * @param $subSearchText
     * @return null|VideoClip
     */
    public function getVideoClipByBySubSearchText(Video $video, $subSearchText) {
        if(empty($subSearchText) || empty($video)) {
            return null;
        }
        $subSearchText = str_replace(PHP_EOL, "\r\n", $subSearchText);
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        $qb = $repository->createQueryBuilder('vc');
        return $qb
            ->where('vc.videoOrigin = :video AND vc.subSearchText = :subSearchText')
            ->setMaxResults(1)
            ->setParameter('video', $video)
            ->setParameter('subSearchText', strip_tags(trim($subSearchText)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findPublicAll($offset, $limit) {
        /** @var VideoClipRepository $seriesRepository */
        $repository = $this->getEntityRepository();
        return $repository->findBy(array('isPublic'=>true), null, $limit, $offset);
    }
    
    public function removeClipsByVideo(Video $video) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->deleteByVideo($video);
    }

    public function getRandomLongVideoClip($notIn = [], $minEase = 90, $maxEase = 100) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findRandomClip($notIn, $minEase, $maxEase);
    }

    public function getClipsByParent(VideoClip $videoClip) {
        /** @var VideoClipRepository $repository */
        $repository = $this->getEntityRepository();
        return $repository->findByParent($videoClip);
    }
}
