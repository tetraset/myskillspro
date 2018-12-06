<?php
namespace MyskillsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class DictWordRuAdmin extends DictWordAdmin
{
    protected $lang = 'ru';

    public function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('word')
            ->add('translationsCnt', 'integer', array('label'=>'quantity of translations'));
    }

    public function configureFormFields( FormMapper $form )
    {
        $form
            ->add('word', 'text', array('label' => 'word'));

        if( $this->getSubject()->getWord() ) {
            $form
                ->add('translationsEn', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                    'by_reference' => false,
                    'required' => false
                ), array(
                    'edit' => 'inline',
                    'sortable' => 'priority',
                    'admin_code' => 'admin.translations.ru.en'
                ))
            ;
        }
    }

    public function preUpdate($project)
    {
        $project->setTranslationsEn($project->getTranslationsEn());
        $project->countTranslations();
    }
}