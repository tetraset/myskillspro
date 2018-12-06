<?php
namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;

/**
 * DictTranslationEnRu
 *
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\DictTranslationRuRepository")
 * @ORM\Table(name="dict_translation_en_ru", uniqueConstraints={@ORM\UniqueConstraint(name="word_source_uniq", columns={"hash"})}, indexes={@ORM\Index(name="id_source", columns={"id_source", "time_add"}), @ORM\Index(name="id_user", columns={"id_user", "time_add"}), @ORM\Index(name="id_word_en", columns={"id_word", "is_public", "priority", "time_add", "votes_cnt"})})
 * @ORM\HasLifecycleCallbacks()
 */
class DictTranslationEnRu extends DictTranslation {
    /**
     * @ORM\ManyToOne(targetEntity="DictWordEn", inversedBy="translationsRu", cascade={"persist"})
     * @ORM\JoinColumn(name="id_word", referencedColumnName="id_word")
     */
    protected $wordContainer;

    const LANG_WORD = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="html_translation", type="text", nullable=false)
     * @Accessor(getter="getHtmlTranslation")
     */
    protected $htmlTranslation;

    /**
     * @return string
     */
    public function getHtmlTranslation($withConvertation=true)
    {
        if($withConvertation) {
            return self::convertHtmlTranslation(
                parent::getHtmlTranslation(), self::LANG_WORD
            );
        }
        return parent::getHtmlTranslation();
    }
}
