<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * DictWordEn
 *
 * @ORM\Table(name="dict_word_en", uniqueConstraints={@ORM\UniqueConstraint(name="word", columns={"word"})}, indexes={
 * @ORM\Index(name="words_cnt", columns={"words_cnt"})
 * })
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\DictWordEnRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class DictWordEn extends DictWord
{
    const LANG = 'en';

    /**
     * @ORM\OneToMany(targetEntity="DictTranslationEnRu", mappedBy="wordContainer", cascade={"persist", "remove"})
     * @Exclude
     */
    protected $translationsRu;

    /**
     * @ORM\OneToMany(targetEntity="AudioClip", mappedBy="wordContainer", cascade={"persist", "remove"})
     * @Exclude
     */
    protected $audioClips;

    /**
     * Используется для парсинга
     * @var boolean
     *
     * @ORM\Column(name="is_checked", type="boolean", nullable=false)
     * @Exclude
     */
    protected $checked = false;

    /**
     * Используется для парсинга аудио
     * @var boolean
     *
     * @ORM\Column(name="checked_audio_clips", type="boolean", nullable=false)
     * @Exclude
     */
    protected $checkedAudioClips = false;

    /**
     * @Exclude
     */
    private $limitPublicTranslations = 1;

    public function __construct()
    {
        $this->translationsRu = new ArrayCollection();
        $this->audioClips = new ArrayCollection();
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
     * @param mixed $translationsRu
     */
    public function setTranslationsRu($translationsRu)
    {
        $this->translationsRu = $translationsRu;
    }

    public function addTranslationsRu( $translationRu )
    {
        $translationRu->setWordContainer( $this );
        $this->translationsRu->add( $translationRu );
    }

    /**
     * @return ArrayCollection
     */
    public function getTranslationsRu(Criteria $criteria=null)
    {
        if ( $criteria ) {
            return $this->translationsRu->matching( $criteria );
        }
        return $this->translationsRu;
    }

    public function isSourceTranslationExist( $idSource ) {
        $translations = $this->getTranslationsRu()->toArray();
        if(empty($translations)) {
            return false;
        }
        /** @var DictTranslationEnRu $t */
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
    public function getPublicTranslationsRu($withLimit=true) {
        $translations = $this->getTranslationsRu(
            $this->getCriteriaForPublicTranslations(true)
        )->toArray();

        if(!empty($translations)) {
            usort($translations, function(DictTranslationEnRu $t1, DictTranslationEnRu $t2) {
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
        return $this->getPublicTranslationsRu(false);
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
        return count($this->getPublicTranslationsRu(false));
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
     * @return ArrayCollection
     */
    public function getAudioClips(Criteria $criteria=null)
    {
        if ( $criteria ) {
            return $this->audioClips->matching( $criteria );
        }
        return $this->audioClips;
    }

    /**
     * @return mixed
     */
    public function getPublicAudioClips()
    {
        return $this->getAudioClips(
            $this->getCriteriaForPublicAudioClips(true)
        )->toArray();
    }

    /**
     * @param mixed $audioClips
     */
    public function setAudioClips($audioClips)
    {
        $this->audioClips = $audioClips;
    }

    public function addAudioClip(AudioClip $audioClip) {
        $this->audioClips->add($audioClip);
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
     * @return boolean
     */
    public function isCheckedAudioClips()
    {
        return $this->checkedAudioClips;
    }

    /**
     * @param boolean $checkedAudioClips
     */
    public function setCheckedAudioClips($checkedAudioClips)
    {
        $this->checkedAudioClips = $checkedAudioClips;
    }

    /**
     * @return string
     */
    public function getPublicHtmlTranslation()
    {
        return DictTranslation::convertHtmlTranslation(
            $this->publicHtmlTranslation,
            'en'
        );
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpdateTasks()
    {
        $this->countWords();
        $translations = $this->getPublicTranslationsRu();
        if(!empty($translations)) {
            /** @var DictTranslationEnRu $translation */
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
