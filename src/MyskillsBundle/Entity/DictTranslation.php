<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;

/**
 * Class DictTranslation
 * @ORM\HasLifecycleCallbacks()
 */
abstract class DictTranslation implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_translation", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Exclude
     */
    protected $idTranslation;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_word", type="integer", nullable=false)
     * @Exclude
     */
    protected $idWord;

    /**
     * @var string
     *
     * @ORM\Column(name="html_translation", type="text", nullable=false)
     */
    protected $htmlTranslation;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="text", nullable=false)
     */
    protected $translation;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_source", type="integer", nullable=true)
     * @Exclude
     */
    protected $idSource;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=true)
     * @Exclude
     */
    protected $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="login_user", type="string", length=100, nullable=true)
     */
    protected $loginUser;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean")
     * @Exclude
     */
    protected $isPublic = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_add", type="datetime", nullable=false)
     * @Exclude
     */
    protected $timeAdd;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer", nullable=false)
     * @Exclude
     */
    protected $priority = 10;

    /**
     * @var integer
     *
     * @ORM\Column(name="votes_cnt", type="integer", nullable=false)
     * @Exclude
     */
    protected $votesCnt = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="comments_cnt", type="integer", nullable=false)
     * @Exclude
     */
    protected $commentsCnt = 0;

    /**
     * @ORM\ManyToOne(targetEntity="DictSource")
     * @ORM\JoinColumn(name="id_source", referencedColumnName="id_source")
     */
    protected $source;
    
    protected $wordContainer;

    /**
     * Примеры применения
     * @var string
     *
     * @ORM\Column(name="html_example", type="text", nullable=true)
     */
    protected $htmlExample;

    /**
     * Примеры применения
     * @var string
     *
     * @ORM\Column(name="example", type="text", nullable=true)
     */
    protected $example;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=50, nullable=false)
     * @Exclude
     */
    protected $hash;

    /**
     * @return string
     */
    public function getHtmlExample()
    {
        return $this->htmlExample;
    }

    /**
     * @param string $htmlExample
     */
    public function setHtmlExample($htmlExample)
    {
        $this->htmlExample = $htmlExample;
    }

    /**
     * @return string
     */
    public function getExample()
    {
        return $this->example;
    }

    /**
     * @param string $example
     */
    public function setExample($example)
    {
        $this->example = $example;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        $this->preUpdateTasks();
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }
    
    public function getObjectIdentifier() {
        return $this->idTranslation;
    }
    
    /**
     * @return DictWord
     */
    public function getWordContainer() {
        return $this->wordContainer;
    }

    /**
     * @param DictWord $wordContainer
     */
    public function setWordContainer( DictWord $wordContainer ) {
        $this->wordContainer = $wordContainer;
    }

    /**
     * @return DictSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param DictSource $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getIdTranslation()
    {
        return $this->idTranslation;
    }

    /**
     * @return int
     */
    public function getIdWord()
    {
        return $this->idWord;
    }

    /**
     * @param int $idWord
     */
    public function setIdWord($idWord)
    {
        $this->idWord = $idWord;
    }

    /**
     * Set translation
     *
     * @param string $translation
     *
     * @return DictTranslation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Get translation
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @return string
     */
    public function getHtmlTranslation()
    {
        return $this->htmlTranslation;
    }

    /**
     * @param $html
     * @param $lang
     * @return string
     */
    public static function convertHtmlTranslation($html, $lang) {
        $html = preg_replace('/<(span|strong) class="link">(.+)<\/(span|strong)>/ismuU', '<a target="_blank" href="/'.$lang.'/$2?target=dictionary">$2</a>', $html);
        return nl2br($html);
    }

    /**
     * @param string $htmlTranslation
     *
     * @return DictTranslation
     */
    public function setHtmlTranslation($htmlTranslation)
    {
        $this->htmlTranslation = $htmlTranslation;

        return $this;
    }


    /**
     * Set idSource
     *
     * @param integer $idSource
     *
     * @return DictTranslation
     */
    public function setIdSource($idSource)
    {
        $this->idSource = $idSource;

        return $this;
    }

    /**
     * Get idSource
     *
     * @return integer
     */
    public function getIdSource()
    {
        return $this->idSource;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return DictTranslation
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     *
     * @return DictTranslation
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set timeAdd
     *
     * @param \DateTime $timeAdd
     *
     * @return DictTranslation
     */
    public function setTimeAdd($timeAdd)
    {
        $this->timeAdd = $timeAdd;

        return $this;
    }

    /**
     * Get timeAdd
     *
     * @return \DateTime
     */
    public function getTimeAdd()
    {
        return $this->timeAdd;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     *
     * @return DictTranslation
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set votes
     *
     * @param integer $votesCnt
     *
     * @return DictTranslation
     */
    public function setVotesCnt($votesCnt)
    {
        $this->votesCnt = $votesCnt;

        return $this;
    }

    /**
     * Get votes
     *
     * @return integer
     */
    public function getVotesCnt()
    {
        return $this->votesCnt;
    }

    /**
     * Set comments
     *
     * @param integer $commentsCnt
     *
     * @return DictTranslation
     */
    public function setCommentsCnt($commentsCnt)
    {
        $this->commentsCnt = $commentsCnt;

        return $this;
    }

    /**
     * Get comments
     *
     * @return integer
     */
    public function getCommentsCnt()
    {
        return $this->commentsCnt;
    }

    public function __construct() {
        $this->timeAdd = new \DateTime();
    }

    /**
     * @return string
     */
    public function getLoginUser()
    {
        return $this->loginUser;
    }

    /**
     * @param string $loginUser
     */
    public function setLoginUser($loginUser)
    {
        $this->loginUser = $loginUser;
    }


    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->setHtmlTranslation(
            trim(
                preg_replace( '/<a.+>(.+)<\/a>/iUsm', '<strong class="link">$1</strong>', $this->htmlTranslation )
            )
        );
        $this->setTranslation(
            trim(
                strip_tags(
                    preg_replace( '/&.+;/iUsm', '', $this->htmlTranslation )
                )
            )
        );
        $this->setHtmlExample(
            trim(
                preg_replace( '/<a.+>(.+)<\/a>/iUsm', '<strong class="link">$1</strong>', $this->htmlExample )
            )
        );
        $this->setExample(
            trim(
                strip_tags(
                    preg_replace( '/&.+;/iUsm', '', $this->htmlExample )
                )
            )
        );
        $this->hash = $this->idWord.'_'.$this->idSource.'_'.md5($this->translation);
    }
}
