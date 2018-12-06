<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Dictionary\DictionaryEnRuManager;
use MyskillsBundle\DomainManager\Dictionary\DictionaryRuEnManager;
use MyskillsBundle\Entity\DictWord;
use MyskillsBundle\Entity\DictWordEn;
use MyskillsBundle\Entity\DictWordRu;
use MyskillsBundle\Exception\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(service="dictionary.controller")
 */
class DictionaryController extends BaseController
{
    /** @var  SearchController */
    private $searchController;

    /** @var DictionaryRuEnManager */
    private $ruEnManager;

    public function __construct(
        BaseDomainManager $domainManager,
        DictionaryRuEnManager $ruEnManager,
        SearchController $searchController
    )
    {
        $this->searchController = $searchController;
        $this->ruEnManager = $ruEnManager;
        parent::__construct($domainManager);
    }

    /**
     * @Route("/en/{word}", name="wordenpage")
     */
    public function enWordAction($word, Request $request)
    {
        /** @var DictionaryEnRuManager $manager */
        $manager = $this->getDomainManager();
        try {
            /** @var DictWordEn $origWord */
            $origWord = $manager->getByWord($word);
        } catch(EntityNotFoundException $e) {
            throw $this->createNotFoundException('The word or public translations does not exist');
        }

        return $this->getWord($origWord, $origWord->getPublicTranslationsWithoutLimit(), 'en', $request, $manager);
    }

    /**
     * @Route("/ru/{word}", name="wordrupage")
     */
    public function ruWordAction($word, Request $request)
    {
        /** @var DictionaryEnRuManager $manager */
        $manager = $this->ruEnManager;
        try {
            /** @var DictWordRu $origWord */
            $origWord = $manager->getByWord($word);
        } catch(EntityNotFoundException $e) {
            throw $this->createNotFoundException('The word or public translations does not exist');
        }

        return $this->getWord($origWord, $origWord->getPublicTranslationsWithoutLimit(), 'ru', $request, $manager);
    }

    /**
     * @param $origWord
     * @param $translations
     * @param $lang
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getWord(DictWord $origWord, $translations, $lang, Request $request, $manager) {
        $user = $this->getUser();
        $translations = $manager->manageTranslations($translations, $user);
        $csrfToken = $this->getTokenizer()->setAccessToken(DictionaryEnRuManager::TOKEN_PREFIX);

        $dataArr = array(
            'word' => $origWord->getWord(),
            'audioList' => $origWord->getPublicAudioClips(),
            'lang' => $lang,
            'id_word' => $origWord->getIdWord(),
            'translations_cnt' => $origWord->countPublicTranslations(),
            'translations' => $translations,
            'csrf_token' => $csrfToken,
            'csrf_prefix' => DictionaryEnRuManager::TOKEN_PREFIX,
        );

        return $this->render('MyskillsBundle:Video:word.html.twig', $dataArr);
    }


}
