<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * DictWordRu
 *
 * @ORM\Table(name="dict_word_ru", uniqueConstraints={@ORM\UniqueConstraint(name="word", columns={"word"})}, indexes={
 * @ORM\Index(name="words_cnt", columns={"words_cnt"})
 * })
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\DictWordRuRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class DictWordRu extends DictWord
{
    const LANG = 'ru';

    /**
     * @ORM\OneToMany(targetEntity="DictTranslationRuEn", mappedBy="wordContainer", cascade={"persist", "remove"})
     * @Exclude
     */
    protected $translationsEn;

    /**
     * Используется для парсинга
     * @var boolean
     *
     * @ORM\Column(name="is_checked", type="boolean", nullable=false)
     * @Exclude
     */
    protected $checked = false;

    /**
     * @Exclude
     */
    private $limitPublicTranslations = 1;

    public function getPublicAudioClips() {
        return [];
    }

    public function __construct()
    {
        $this->translationsEn = new ArrayCollection();
    }

    /**
     * @return boolean
     */
    public function isChecked()
    {
        return $this->checked;
    }

    /**
     * @param boolean $checked
     */
    public function setChecked($checked)
    {
        $this->checked = $checked;
    }

    /**
     * @param mixed $translationsEn
     */
    public function setTranslationsEn($translationsEn)
    {
        $this->translationsEn = $translationsEn;
    }

    public function addTranslationsEn( $translationEn )
    {
        $translationEn->setWordContainer( $this );
        $this->translationsEn->add( $translationEn );
    }

    /**
     * @return ArrayCollection
     */
    public function getTranslationsEn(Criteria $criteria=null)
    {
        if ( $criteria ) {
            return $this->translationsEn->matching( $criteria );
        }
        return $this->translationsEn;
    }

    public function isSourceTranslationExist( $idSource ) {
        $translations = $this->translationsEn->toArray();
        foreach ($translations as $t) {
            if ($t->getIdSource() == $idSource) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getPublicTranslationsEn($withLimit=true) {
        $translations = $this->getTranslationsEn(
            $this->getCriteriaForPublicTranslations(true)
        )->toArray();

        if(!empty($translations)) {
            usort($translations, function(DictTranslationRuEn $t1, DictTranslationRuEn $t2) {
                if ($t1->getSource()->getPriority() == $t2->getSource()->getPriority()) {
                    return $t1->getIdTranslation() < $t2->getIdTranslation() ? -1 : 1;
                }
                return $t1->getSource()->getPriority() > $t2->getSource()->getPriority() ? -1 : 1;
            });
            if($withLimit) {
                $translations = array_slice($translations, 0, $this->limitPublicTranslations);
            }
        }

        return array_values($translations);
    }

    public function getPublicTranslationsWithoutLimit() {
        return $this->getPublicTranslationsEn(false);
    }

    public function countTranslations() {
        $this->setTranslationsCnt(
            $this->countPublicTranslations()
        );
    }

    public function getTotalPublicTranslations() {
        $this->countTranslations();
        return $this->getTranslationsCnt();
    }

    /**
     * @return int
     */
    public function countPublicTranslations() {
        return count($this->getPublicTranslationsEn(false));
    }

    /**
     * @return int
     */
    public function getLimitPublicTranslations()
    {
        return $this->limitPublicTranslations;
    }

    /**
     * @param int $limitPublicTranslations
     */
    public function setLimitPublicTranslations($limitPublicTranslations)
    {
        $this->limitPublicTranslations = $limitPublicTranslations;
    }

    /**
     * Get getCriteriaForPublicAudioClips
     *
     * @return Criteria
     */
    protected function getCriteriaForPublicAudioClips($isPublic = true)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('isPublic', $isPublic));
        return $criteria;
    }

    /**
     * @return string
     */
    public function getPublicHtmlTranslation()
    {
        return DictTranslation::convertHtmlTranslation(
            $this->publicHtmlTranslation,
            'ru'
        );
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpdateTasks()
    {
        $this->countWords();
        $translations = $this->getPublicTranslationsEn();
        if(!empty($translations)) {
            /** @var DictTranslationRuEn $translation */
            $translation = current($translations);

            $this->publicHtmlTranslation = $translation->getHtmlTranslation();
            if($translation->getLoginUser()) {
                $this->publicSource = 'Translated by ' . $translation->getLoginUser();
            } else {
                /** @var DictSource $source */
                $source = $translation->getSource();
                $this->publicSource = '<a href="'. $source->getLink() .'" target="_blank">' . $source->getSource() . '</a>';
            }
        }
    }
}
