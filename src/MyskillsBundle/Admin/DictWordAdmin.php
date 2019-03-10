<?php
namespace MyskillsBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use MyskillsBundle\Entity\DictWord;

class DictWordAdmin extends Admin
{
    protected $lang;

    public function prePersist($project)
    {
        $this->preUpdate($project);
    }

    public function preUpdate($project)
    {
        $project->setTranslationsRu($project->getTranslationsRu());
        $project->setTranslationsEn($project->getTranslationsEn());
        $project->countTranslations();
    }

    public function getLang() {
        return $this->lang;
    }

    public function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('word')
            ->add('translationsCnt', 'integer', array('label'=>'quantity of translations'));
    }

    public function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('word', 'doctrine_orm_callback', array(
                'callback' => array($this, 'getFullTextFilter'),
                'field_type' => 'text'
            ));
    }

    public function toString($object)
    {
        return $object instanceof DictWord
            ? $object->getWord()
            : 'Word for translation';
    }

    public function getFullTextFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }
        $queryBuilder->where($queryBuilder->expr()->like($alias.'.word', $queryBuilder->expr()->literal($value['value'] . '%')));
        return true;
    }
}