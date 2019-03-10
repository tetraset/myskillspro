<?php
namespace MyskillsBundle\DomainManager\ApiTranslation;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\AudioClip;
use MyskillsBundle\Entity\DictTranslation;
use MyskillsBundle\Entity\DictTranslationEnRu;
use MyskillsBundle\Entity\DictWordEn;
use MyskillsBundle\Exception\InvalidArgumentException;
use MyskillsBundle\Repository\DictSourceRepository;
use MyskillsBundle\Repository\DictTranslationEnRepository;
use MyskillsBundle\Repository\DictTranslationRuRepository;
use MyskillsBundle\Repository\DictWordEnRepository;
use MyskillsBundle\Service\SkyEngTranslatorService;
use MyskillsBundle\Service\YandexTranslatorService;
use Application\Sonata\UserBundle\Entity\User;
use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;
use Doctrine\ORM\EntityManager;

class ApiTranslationManager extends BaseDomainManager
{
    const WORDS_EN_INDEX = 'words_en_ru';
    const WORDS_RU_INDEX = 'words_ru_en';
    const SEARCH_LIMIT_ON_PAGE = 10;
    const SKYENG_API_SOURCE_ID = 60189;
    const YANDEX_API_SOURCE_ID = 60184;
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month
    const LIMIT_SPACE = 6;
    const USER_SOURCE_ID = 60188;
    private $skyengTranslator;
    private $yandexTranslator;
    private $sphinxService;
    private $sourceRepository;
    private $translationEnRuRepository;
    private $translationRuEnRepository;
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(
        DictWordEnRepository $baseRepository,
        DictSourceRepository $sourceRepository,
        DictTranslationRuRepository $translationEnRuRepository,
        DictTranslationEnRepository $translationRuEnRepository,
        YandexTranslatorService $yandexTranslator,
        SkyEngTranslatorService $skyengTranslator,
        Sphinxsearch $sphinxService,
        EntityManager $em
    )
    {
        parent::__construct($baseRepository);
        $this->yandexTranslator = $yandexTranslator;
        $this->skyengTranslator = $skyengTranslator;
        $this->sphinxService = $sphinxService;
        $this->sourceRepository = $sourceRepository;
        $this->translationEnRuRepository = $translationEnRuRepository;
        $this->translationRuEnRepository = $translationRuEnRepository;
        $this->em = $em;
    }

    /**
     * @param $word
     * @param $userTranslation
     * @param User $user
     * @return array
     * @throws InvalidArgumentException
     */
    public function addTranslation($word, $userTranslation, User $user) {
        /** @var DictWordEn $oldWord */
        $oldWord = current($this->searchWordInBase($word, false));
        if(empty($oldWord)) {
            /** @var DictWordEnRepository $wordRepository */
            $wordRepository = $this->getEntityRepository();
            $newWord = new DictWordEn();
            $newWord->setWord($word);

            $this->em->persist($newWord);
            $this->em->flush();
            $oldWord = $wordRepository->find($newWord->getIdWord());
        }

        $translation = new DictTranslationEnRu();
        $translation->setHtmlTranslation($userTranslation);
        $translation->setIdWord($oldWord->getIdWord());
        $translation->setIdSource(self::USER_SOURCE_ID);
        $translation->setSource($this->sourceRepository->findOneByIdSource(self::USER_SOURCE_ID));
        $translation->setIsPublic(true);
        $translation->setIdUser($user->getId());
        $translation->setLoginUser($user->getPassword() ? $user->getUsername() : trim($user->getFirstname() . ' ' . $user->getLastname()));

        if($this->hasCopiedTranslation($translation, $oldWord->getTranslationsRu()->toArray())) {
            throw new InvalidArgumentException("Данный перевод уже существует");
        }

        $oldWord->addTranslationsRu($translation);
        $oldWord->countTranslations();
        $oldWord->preUpdateTasks();

        $this->em->persist($translation);
        $this->em->flush();

        return [$oldWord];
    }

    /**
     * @param $idTranslation
     * @param User $user
     * @throws InvalidArgumentException
     */
    public function removeTranslation($idTranslation, User $user) {
        /** @var DictTranslationEnRu $translation */
        $translation = $this->translationEnRuRepository->findOneBy(['idTranslation'=>$idTranslation, 'idUser'=>$user->getId()]);
        if(null === $translation) {
            throw new InvalidArgumentException("Данного перевода нет или он был удален раньше");
        }
        /** @var DictWordEn $word */
        $word = $translation->getWordContainer();

        $this->em->remove($translation);
        $this->em->flush();

        $word->countTranslations();
        $word->preUpdateTasks();
        $this->em->flush();
    }

    private function hasCopiedTranslation(DictTranslation $translation, array $translations) {
        $hash = $translation->getHash();
        $hashes = array_map(function(DictTranslation $t){
            return $t->getHash();
        }, $translations);

        if(in_array($hash, $hashes)) {
            return true;
        }

        $htmlTranslation = mb_strtolower(trim($translation->getHtmlTranslation()));
        $htmlTranslations = array_map(function(DictTranslation $t){
            return mb_strtolower(trim($t->getHtmlTranslation()));
        }, $translations);

        return in_array($htmlTranslation, $htmlTranslations);
    }

    private function filterEntitiesWithoutTranslations(array $results_arr) {
        if(empty($results_arr)) {
            return $results_arr;
        }
        return array_filter(
            $results_arr,
            function(DictWordEn $w) {
                return (bool) $w->getTranslationsCnt();
            }
        );
    }

    /**
     * @param $word
     * @param bool $withLinkedWords
     * @return array
     */
    public function translate($word, $withLinkedWords=true) {
        $results_arr = [];

        if ($word) {
            $results_arr = $this->filterEntitiesWithoutTranslations(
                $this->searchWordInBase($word)
            );

            if(empty($results_arr)) {
                $results_arr = $results_arr = $this->filterEntitiesWithoutTranslations(
                    $this->searchBySphinx($word)
                );
            }

            // step 2: search in yandex & skyeng api
            if (empty($results_arr)) {
                $results_arr = array_merge(
                    $this->searchInRemoteApi($word, self::YANDEX_API_SOURCE_ID, true)
                );
                if (strpos($word, ' ') === false) {
                    $results_arr = array_merge(
                        $this->searchInRemoteApi($word, self::SKYENG_API_SOURCE_ID, true)
                    );
                }
            } elseif ($withLinkedWords) {
                $results_arr = array_merge(
                    $this->addLinkedWords($results_arr)
                );
            }
            if(!empty($results_arr)) {
                $results_arr[] = "<br /><p style='text-align: center'><a style='color: #999' target='_blank' href='/en/$word'>Найти еще в словарях</a></p>";
            }
            $results_arr[] = "<p style='text-align: center'><a style='color: #999' href='#' onclick='addOwnTranslation(\"" . $word .  "\");return false;'>Добавить свой перевод</a></p>";
        }

        return array_unique($results_arr);
    }

    /**
     * @param $word
     * @return array
     */
    private function searchBySphinx($word) {
        $this->sphinxService->setLimits(0, self::SEARCH_LIMIT_ON_PAGE);
        $this->sphinxService->SetMatchMode(SPH_MATCH_PHRASE);
        $this->sphinxService->SetSortMode(SPH_SORT_RELEVANCE);
        $results = $this->sphinxService->searchEx($word, self::WORDS_EN_INDEX);
        $results_arr = [];
        if ( $results['total_found'] ) {
            // step 1: search in our base
            foreach ($results['matches'] as $match) {
                /** @var DictWordEn $w */
                $w = $match['entity'];

                if(null === $w) {
                    continue;
                }
                $words = $this->getArrayResults($w->getWord());

                if(!empty($words)) {
                    foreach ($words as $w) {
                        if($w == $word) {
                            $results_arr[] = $match['entity'];
                            continue 2;
                        }
                    }
                }
            }
        }
        return $results_arr;
    }

    /**
     * @param $words
     * @return array
     */
    private function getArrayResults($words) {
        if(empty($words)) {
            return [];
        }
        $results = [];
        if(strpos($words, ', ') !== false) {
            $results = explode(', ', $words);
        }
        if(strpos($words, '・') !== false) {
            $results = explode('・', $words);
        }
        if(!is_array($results)) {
            $results = [$words];
        }
        return $results;
    }

    /**
     * @param $word
     * @return array
     */
    private function searchWordInBase($word, $withCache=true) {
        /** @var DictWordEnRepository $wordRepository */
        $wordRepository = $this->getEntityRepository();
        $oldWord = $wordRepository->findOneByWord($word, $withCache);
        return $oldWord !== null ? [$oldWord] : [];
    }

    /**
     * @param $word
     * @param $idSource
     * @param bool $oldWordCheck
     * @return array
     */
    private function searchInRemoteApi($word, $idSource, $oldWordCheck=false) {
        $translate = null;
        $oldWord = null;
        $translateObjs = [];
        $audioList = [];

        if($oldWordCheck) {
            $oldWords = $this->searchWordInBase($word, false);
            if(!empty($oldWords)) {
                /** @var DictWordEn $oldWord */
                $oldWord = $oldWords[0];
                if($oldWord->isSourceTranslationExist($idSource)) {
                    return [];
                }
            }
        }

        switch($idSource) {
            case self::YANDEX_API_SOURCE_ID:
                $translate = $this->yandexTranslator->translate($word, 'en', 'ru');
                break;
            case self::SKYENG_API_SOURCE_ID:
                $translateObjs = $this->skyengTranslator->translate($word);
                break;
            default:
                return [];
        }

        if (!empty($translateObjs)) {
            foreach ($translateObjs as $translateObj) {
                if (!empty($translateObj['imageUrl'])) {
                    $translate .= "<p style='text-align: center'><img class='skyeng_img' src='{$translateObj['imageUrl']}' /></p>";
                }
                $translate .= "<p>{$translateObj['translation']['text']} [{$translateObj['transcription']}] ({$translateObj['partOfSpeechCode']})";

                if (!empty($translateObj['translation']['note'])) {
                    $translate .= " " . $translateObj['translation']['note'];
                }
                $translate .= "</p>";

                if (!empty($translateObj['soundUrl'])) {
                    $audioList[] = $translateObj['soundUrl'];
                }
            }
        }

        if(empty($translate)) {
            return [];
        }
        $translate = strip_tags($translate, '<p><img><hr>');

        if($oldWord !== null) {
            $newWord = $oldWord;
        } else {
            $newWord = new DictWordEn();
            $newWord->setWord($word);
            $this->em->persist($newWord);
            $this->em->flush();
            $this->em->refresh($newWord);
        }

        if (!empty($audioList)) {
            foreach ($audioList as $audioUrl) {
                $newWord->addAudioClip(
                    new AudioClip(
                        'skyeng_' . md5($audioUrl),
                        $audioUrl,
                        true,
                        $newWord,
                        $word
                    )
                );
            }
        }

        $translation = new DictTranslationEnRu();
        $translation->setHtmlTranslation($translate);
        $translation->setIdWord($newWord->getIdWord());

        $translation->setIdSource($idSource);
        $translation->setSource($this->sourceRepository->findOneByIdSource($idSource));
        $translation->setIsPublic(true);

        $newWord->addTranslationsRu($translation);
        $newWord->countTranslations();
        $newWord->preUpdateTasks();

        $this->em->flush();

        return [$newWord];
    }

    /**
     * @param array $words
     * @return array
     */
    private function addLinkedWords(array $words) {
        if(empty($words)) {
            return [];
        }
        $linkRegexp = '/<(span|strong) class="link">(?P<link_word>[^<]+)<\/(span|strong)>/i';
        $results_arr = $words;
        /** @var DictWordEn $w */
        foreach ($words as $w) {
            $translations = $w->getPublicTranslationsRu();
            if(!empty($translations)) {
                /** @var DictTranslationEnRu $t */
                foreach ($translations as $t) {
                    $html = $t->getHtmlTranslation();
                    if(preg_match($linkRegexp, $html, $match)) {
                        $results_arr = array_merge($results_arr, $this->translate($match['link_word'], false));
                    }
                }
            }
        }
        return $results_arr;
    }

    /**
     * @param $w
     * @return string
     */
    public function limitWords($w) {
        $word = trim(mb_strtolower($w));
        $words = preg_split("/\s+/", $word);
        return implode(' ', array_slice($words, 0, self::LIMIT_SPACE));
    }
}
