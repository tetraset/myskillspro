<?php
namespace MyskillsBundle\DomainManager\UserDictionary;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Series\SeriesManager;
use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Entity\DictWordEn;
use MyskillsBundle\Entity\UserFolder;
use MyskillsBundle\Entity\UserWord;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Exception\LimitUserDictionaryException;
use MyskillsBundle\Repository\DictWordEnRepository;
use MyskillsBundle\Repository\UserFolderRepository;
use MyskillsBundle\Repository\UserWordRepository;
use Doctrine\ORM\EntityManager;

class UserDictionaryManager extends BaseDomainManager
{
    const FREE_WORDS_LIMIT = 50;
    const LIMIT_FOLDERS_WORDS_ON_PAGE = 20;
    const TOKEN_PREFIX = '_user_dictionary';
    
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DictWordEnRepository
     */
    private $enWordRepository;

    /**
     * @var UserFolderRepository
     */
    private $userFolderRepository;

    /**
     * @var VideoClipManager
     */
    private $videoClipManager;

    private $videoManager;

    public function __construct(
        UserWordRepository $baseRepository,
        DictWordEnRepository $enWordRepository,
        UserFolderRepository $userFolderRepository,
        VideoManager $videoManager,
        VideoClipManager $videoClipManager,
        EntityManager $em
    )
    {
        parent::__construct($baseRepository);
        $this->em = $em;
        $this->enWordRepository = $enWordRepository;
        $this->userFolderRepository = $userFolderRepository;
        $this->videoClipManager = $videoClipManager;
        $this->videoManager = $videoManager;
    }

    /**
     * @param $idUser
     * @param array $relocateWords
     * @param int $idFolder
     */
    public function relocateWords($idUser, array $relocateWords=[], $idFolder=0) {
        /** @var UserWordRepository $wordRepository */
        $wordRepository = $this->getEntityRepository();
        /** @var UserFolder $folder */
        $folder = $this->userFolderRepository->findOneBy(['id' => $idFolder, 'idUser' => $idUser]);
        $words = $wordRepository->findBy(['id' => $relocateWords, 'idUser' => $idUser]);
        if(!empty($words) && (null !== $folder || $idFolder == 0)) {
            $updateFolders = [];
            /** @var UserWord $w */
            foreach($words as &$w) {
                if(!empty($w->getIdFolder())) {
                    $updateFolders[] = $w->getIdFolder();
                }
                $w->setIdFolder($idFolder);
                if($idFolder) {
                    $folder->addUserWord($w);
                }
            }
            if($idFolder) {
                $updateFolders[] = $idFolder;
                $folder->setIsDeleted(false);
            }
            $this->em->flush();
            $this->recountChildren($updateFolders);
        }
    }

    /**
     * @param array $foldersIds
     */
    public function recountChildren(array $foldersIds=[]) {
        if(empty($foldersIds)) {
            return;
        }
        $folders = $this->userFolderRepository->findById($foldersIds);
        if(!empty($folders)) {
            foreach($folders as &$f) {
                /** @var UserFolder $f */
                $f->countChildren();
            }
            $this->em->flush();
        }
    }

    /**
     * @param $idUser
     * @param array $relocateFolders
     * @param int $idFolder
     */
    public function relocateFolders($idUser, array $relocateFolders=[], $idFolder=0) {
        $folders = $this->userFolderRepository->findBy(['id' => $relocateFolders, 'idUser' => $idUser]);
        /** @var UserFolder $folder */
        $folder = $this->userFolderRepository->findOneBy(['id' => $idFolder, 'idUser' => $idUser]);
        if(!empty($folders)  && (null !== $folder || $idFolder == 0)) {
            $updateFolders = [];
            /** @var UserFolder $f */
            foreach($folders as &$f) {
                if(!empty($f->getIdParent())) {
                    $updateFolders[] = $f->getIdParent();
                }
                if($f->getId() != $idFolder) {
                    $f->setIdParent($idFolder);
                }
                $f->setIsDeleted(false);
            }
            if ($idFolder) {
                $updateFolders[] = $idFolder;
                $folder->setIsDeleted(false);
            }
            $this->em->flush();
            $this->recountChildren($updateFolders);
        }
    }

    /**
     * @param $idUser
     * @param array $deleteWords
     */
    public function deleteWords($idUser, array $deleteWords=[]) {
        /** @var UserWordRepository $wordRepository */
        $wordRepository = $this->getEntityRepository();
        $words = $wordRepository->findBy(['id' => $deleteWords, 'idUser' => $idUser]);
        if(!empty($words)) {
            $updateFolders = [];
            /** @var UserWord $w */
            foreach($words as &$w) {
                if(!empty($w->getIdFolder())) {
                    $updateFolders[] = $w->getIdFolder();
                }
                $w->setIsDeleted(true);
            }
            $this->em->flush();
            $this->recountChildren($updateFolders);
        }
    }

    /**
     * @param $idUser
     * @param array $deleteFolders
     */
    public function deleteFolders($idUser, array $deleteFolders=[]) {
        $folders = $this->userFolderRepository->findBy(['id' => $deleteFolders, 'idUser' => $idUser]);
        if(!empty($folders)) {
            $updateFolders = [];
            /** @var UserFolder $f */
            foreach($folders as &$f) {
                if(!empty($f->getIdParent())) {
                    $updateFolders[] = $f->getIdParent();
                }
                $f->setIsDeleted(true);
            }
            $this->em->flush();
            $this->recountChildren($updateFolders);
        }
    }

    /**
     * @param $userId
     * @param $page
     * @return array
     */
    public function getLastWords($userId, $page, $idFolder=0) {
        /** @var UserWordRepository $wordRepository */
        $wordRepository = $this->getEntityRepository();
        $lastDateUpdate = $wordRepository->findLastDateUpdate($userId);
        $words = $wordRepository->findLastAddedWords($userId, self::LIMIT_FOLDERS_WORDS_ON_PAGE, $idFolder, $page, $lastDateUpdate);
        $total = $wordRepository->totalWords($userId, $lastDateUpdate, $idFolder);
        $is_more = $total > ($page-1)*self::LIMIT_FOLDERS_WORDS_ON_PAGE + self::LIMIT_FOLDERS_WORDS_ON_PAGE;

        return [
            'items' => $words,
            'total' => $total,
            'is_more' => $is_more
        ];
    }

    /**
     * @param $userId
     * @param int $idFolder
     * @return array
     */
    public function getLastFolders($userId, $idFolder=0) {
        $lastDateUpdate = $this->userFolderRepository->findLastDateUpdate($userId);
        $folders = $this->userFolderRepository->findLastAddedFolders($userId, $idFolder, $lastDateUpdate);
        $total = $this->userFolderRepository->totalFolders($userId, $idFolder, $lastDateUpdate);

        return [
            'items' => $folders,
            'total' => $total
        ];
    }

    /**
     * @param $userId
     * @param $isActiveSubscription
     * @param $idWord
     * @param $idVideo
     * @param $timeOnVideo
     * @param string $subSearchText
     * @param null $hashVideoClip
     * @throws LimitUserDictionaryException
     */
    public function addWord($userId, $isActiveSubscription, $idWord, $idVideo, $timeOnVideo, $subSearchText='', $hashVideoClip=null) {
        /** @var UserWordRepository $wordRepository */
        $wordRepository = $this->getEntityRepository();
        $enWord = $this->enWordRepository->find($idWord);
        $videoClip = null;
        if($hashVideoClip) {
            $videoClip = $this->videoClipManager->getPublicByHash($hashVideoClip);
        }

        /** @var UserWord $oldWord */
        $oldWord = $wordRepository->findOneBy(['enWord'=>$enWord, 'idUser'=>$userId]);
        if(null === $oldWord) {
            $word = new UserWord(
                $enWord,
                $userId,
                $idVideo,
                $timeOnVideo
            );
            $word->setSubSearchText(strip_tags(trim($subSearchText)));
            if(!empty($subSearchText) && !empty($idVideo)) {
                try {
                    $word->setVideoClip(
                        $this->videoClipManager->getVideoClipByBySubSearchText(
                            $this->videoManager->getPublicVideo($idVideo),
                            $subSearchText
                        )
                    );
                } catch(EntityNotFoundException $e) {}
            }
            if(!empty($videoClip)) {
                $word->setVideoClip($videoClip);
            }
            $this->em->persist($word);
            $this->em->flush();
        } elseif ($oldWord->isIsDeleted()) {
            $oldWord->setIsDeleted(false);
            $oldWord->setSubSearchText(strip_tags(trim($subSearchText)));
            if(!empty($subSearchText) && !empty($idVideo)) {
                try {
                    $oldWord->setVideoClip(
                        $this->videoClipManager->getVideoClipByBySubSearchText(
                            $this->videoManager->getPublicVideo($idVideo),
                            $subSearchText
                        )
                    );
                } catch(EntityNotFoundException $e) {}
            }
            if(!empty($videoClip)) {
                $oldWord->setVideoClip($videoClip);
            }
            $this->em->flush();
        }
    }

    /**
     * @param $userId
     * @param $idFolder
     */
    public function addFolder($title, $userId, $idFolder=0) {
        /** @var UserFolder $oldFolder */
        $oldFolder = $this->userFolderRepository->findOneBy(['title'=>$title, 'idUser'=>$userId]);
        if(null === $oldFolder) {
            $folder = new UserFolder($title, $userId);
            $folder->setIdParent($idFolder);
            $this->em->persist($folder);
            $this->em->flush();
        } elseif ($oldFolder->getIsDeleted()) {
            $oldFolder->setIsDeleted(false);
            $oldFolder->setIdParent($idFolder);
            $this->em->flush();
        }
    }
}
