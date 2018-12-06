<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\Entity\DictTranslation;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class DictTranslationAdmin extends Admin
{
    private $wordAdmin;

    public function postRemove( $project )
    {
        $this->preUpdate($project);
    }

    public function postPersist( $project )
    {
        $this->preUpdate($project);
    }

    public function postUpdate( $project )
    {
        if ( $project instanceof DictTranslation && $word = $project->getWordContainer() ) {
            $word->countTranslations();
            $this->wordAdmin->update($word);
        }
    }

    /**
     * @param DictWordAdmin $wordAdmin
     */
    public function setWordAdmin( DictWordAdmin $wordAdmin )
    {
        $this->wordAdmin = $wordAdmin;
    }

    public function configureFormFields( FormMapper $form )
    {
        $form
            ->add('htmlTranslation', 'textarea', array('label' => 'translation'))
            ->add('isPublic', 'checkbox', array('label' => 'published', 'required' => false))
            ->add('source', 'sonata_type_model', array(
                'class' => 'MyskillsBundle\Entity\DictSource',
                'property' => 'source',
            ))
            ->add('wordContainer', 'sonata_type_model_autocomplete', array(
                'class' => 'MyskillsBundle\Entity\DictWord'.ucfirst($this->wordAdmin->getLang()),
                'property' => 'word',
                'label' => 'word',
                'placeholder' => 'word for translation...........................'
            ))
            ->setHelps(array(
                'htmlTranslation' => $this->getSubject() ? $this->getSubject()->getHtmlTranslation() : '',
            ))
        ;
    }

    public function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('idTranslation')
            ->add('wordContainer.word', 'string', array('editable' => true))
            ->addIdentifier('translation', 'string')
            ->add('source.source')
            ->add('isPublic', 'boolean', array('editable' => true))
            ->add('votesCnt')
            ->add('commentsCnt');
    }

    public function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('translation')
            ->add('isPublic')
            ->add('source', null, array('label' => 'source of translation'), 'entity', array(
                'class' => 'MyskillsBundle\Entity\DictSource',
                'property' => 'source'
            ));
    }

    public function toString($object)
    {
        return $object instanceof DictTranslation && $object->getWordContainer()
            ? $object->getWordContainer()->getWord()
            : 'Translation for word';
    }
}