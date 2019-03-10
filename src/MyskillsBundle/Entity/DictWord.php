<?php

namespace MyskillsBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * DictWord
 *
 * @ORM\HasLifecycleCallbacks()
 */
abstract class DictWord implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_word", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $idWord;

    /**
     * @var string
     *
     * @ORM\Column(name="word", type="string", length=255, nullable=false)
     */
    protected $word;

    /**
     * @var integer
     *
     * @ORM\Column(name="words_cnt", type="integer", nullable=false)
     */
    protected $wordsCnt = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="translations_cnt", type="integer", nullable=false)
     * @Exclude
     */
    protected $translationsCnt = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="public_html_translation", type="text", nullable=true)
     * @Accessor(getter="getPublicHtmlTranslation")
     */
    protected $publicHtmlTranslation;

    /**
     * @var string
     *
     * @ORM\Column(name="public_source", type="string", length=255, nullable=true)
     * @Accessor(getter="getPublicSource")
     */
    protected $publicSource;

    /**
     * @param string $publicHtmlTranslation
     */
    public function setPublicHtmlTranslation($publicHtmlTranslation)
    {
        $this->publicHtmlTranslation = $publicHtmlTranslation;
    }

    /**
     * @return string
     */
    public function getPublicSource()
    {
        return $this->publicSource;
    }

    /**
     * @param string $publicSource
     */
    public function setPublicSource($publicSource)
    {
        $this->publicSource = $publicSource;
    }

    /**
     * @return integer
     */
    public function getTranslationsCnt()
    {
        return $this->translationsCnt;
    }

    /**
     * @param integer $translationsCnt
     */
    public function setTranslationsCnt($translationsCnt)
    {
        $this->translationsCnt = $translationsCnt;
    }

    public function getObjectIdentifier()
    {
        return $this->idWord;
    }

    /**
     * Get idWord
     *
     * @return integer
     */
    public function getIdWord()
    {
        return $this->idWord;
    }

    /**
     * Set word
     *
     * @param string $word
     *
     * @return DictWord
     */
    public function setWord($word)
    {
        $this->word = $word;

        return $this;
    }

    /**
     * Get word
     *
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }

    /**
     * Set wordsCnt
     *
     * @param integer $wordsCnt
     *
     * @return DictWord
     */
    public function setWordsCnt($wordsCnt)
    {
        $this->wordsCnt = $wordsCnt;

        return $this;
    }

    /**
     * Get wordsCnt
     *
     * @return integer
     */
    public function getWordsCnt()
    {
        return $this->wordsCnt;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpdateTasks()
    {
        $this->countWords();
    }

    public function countWords()
    {
        $this->word = mb_strtolower($this->word);
        $this->wordsCnt = count(explode(' ', $this->word));
    }

    /**
     * Get getCriteriaForPublicTranslations
     *
     * @return Criteria
     */
    protected function getCriteriaForPublicTranslations($isPublic = true)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('isPublic', $isPublic));
        return $criteria;
    }

    public function __toString()
    {
        return $this->word;
    }

    public abstract function countPublicTranslations();
    public abstract function getPublicAudioClips();
}