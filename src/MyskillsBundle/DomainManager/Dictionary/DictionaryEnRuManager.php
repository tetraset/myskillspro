<?php
namespace MyskillsBundle\DomainManager\Dictionary;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\DictTranslation;
use MyskillsBundle\Entity\DictTranslationEnRu;
use MyskillsBundle\Entity\DictWordEn;
use MyskillsBundle\Exception\EntityNotFoundException;
use MyskillsBundle\Repository\DictWordEnRepository;
use Application\Sonata\UserBundle\Entity\User;

class DictionaryEnRuManager extends BaseDomainManager
{
    const TOKEN_PREFIX = '_enru_dictionary';

    /**
     * @param $word
     * @return DictWordEn
     * @throws EntityNotFoundException
     */
    public function getByWord($word) {
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        $origWord = null;

        /** @var DictWordEn $origWord */
        $origWords = $wordEnRepository->searchWords($word);
        if (empty($origWords)) {
            throw new EntityNotFoundException(DictWordEn::class, $word, 'word');
        }
        $origWord = current($origWords);
        if(!$origWord->countPublicTranslations()) {
            throw new EntityNotFoundException(DictTranslationEnRu::class, $word, 'word');
        }
        return $origWord;
    }

    /**
     * @param $word
     * @return DictWordEn|null
     */
    public function findWord($word)
    {
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        return $wordEnRepository->findOneBy(['word' => $word]);
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
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        return $wordEnRepository->findByChecked($value, [], $limit);
    }

    /**
     * @param $value
     * @param $limit
     * @return array
     */
    public function findByCheckedAudioClips($value, $limit) {
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        return $wordEnRepository->findByCheckedAudioClips($value, [], $limit);
    }

    /**
     * @param DictWordEn $w
     * @return DictWordEn|null
     */
    public function findByWord(DictWordEn $w) {
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        return $wordEnRepository->findOneBy(['word' => $w->getWord()]);
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findAll($offset, $limit) {
        /** @var DictWordEnRepository $wordEnRepository */
        $wordEnRepository = $this->getEntityRepository();
        return $wordEnRepository->findBy(array(), null, $limit, $offset);
    }
}
