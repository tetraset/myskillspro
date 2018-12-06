<?php
namespace MyskillsBundle\DomainManager\Dictionary;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\DictTranslationRuEn;
use MyskillsBundle\Entity\DictWordRu;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\DictWordRuRepository;
use Application\Sonata\UserBundle\Entity\User;

class DictionaryRuEnManager extends BaseDomainManager
{
    const TOKEN_PREFIX = '_ruen_dictionary';

    /**
     * @param $word
     * @return DictWordRu
     * @throws EntityNotFoundException
     */
    public function getByWord($word) {
        /** @var DictWordRuRepository $wordRuRepository */
        $wordRuRepository = $this->getEntityRepository();
        $origWord = null;

        /** @var DictWordRu $origWord */
        $origWords = $wordRuRepository->searchWords($word);
        if (empty($origWords)) {
            throw new EntityNotFoundException(DictWordRu::class, $word, 'word');
        }
        $origWord = current($origWords);
        if(!$origWord->countPublicTranslations()) {
            throw new EntityNotFoundException(DictTranslationRuEn::class, $word, 'word');
        }
        return $origWord;
    }

    /**
     * @param $word
     * @return DictWordRu|null
     */
    public function findWord($word)
    {
        /** @var DictWordRuRepository $wordRuRepository */
        $wordRuRepository = $this->getEntityRepository();
        return $wordRuRepository->findOneBy(['word' => $word]);
    }

    /**
     * @param array $translations
     * @param User $user
     * @return array
     */
    public function manageTranslations(array $translations, $user) {
        if (!empty($translations)) {
            foreach($translations as $key=>$t) {
                if (!$t->getIsPublic()) {
                    unset($translations[$key]);
                    continue;
                }
            }
        }
        return $translations;
    }

    /**
     * @param $value
     * @param $limit
     * @return array
     */
    public function findByChecked($value, $limit) {
        /** @var DictWordRuRepository $wordRuRepository */
        $wordRuRepository = $this->getEntityRepository();
        return $wordRuRepository->findByChecked($value, [], $limit);
    }

    /**
     * @param DictWordRu $w
     * @return DictWordRu|null
     */
    public function findByWord(DictWordRu $w) {
        /** @var DictWordRuRepository $wordRuRepository */
        $wordRuRepository = $this->getEntityRepository();
        return $wordRuRepository->findOneBy(['word' => $w->getWord()]);
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findAll($offset, $limit) {
        /** @var DictWordRuRepository $wordRuRepository */
        $wordRuRepository = $this->getEntityRepository();
        return $wordRuRepository->findBy(array(), null, $limit, $offset);
    }
}
