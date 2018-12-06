<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\Entity\DictSource;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class DictSourceAdmin extends Admin
{
    public function configureFormFields( FormMapper $form )
    {
        $form
            ->add('source', 'text', array('label' => 'title'))
            ->add('link', 'url', array('required' => false))
            ->add('description', 'text', array('required' => false))
            ->add('priority', 'text');
    }

    public function configureListFields( ListMapper $list )
    {
        $list
            ->addIdentifier('source')
            ->add('priority', 'integer', array('editable' => true));
    }

    public function configureDatagridFilters( DatagridMapper $filter )
    {
        $filter
            ->add('source')
            ->add('link');
    }

    public function toString( $object )
    {
        return $object instanceof DictSource
            ? $object->getSource()
            : 'Translation source';
    }
}