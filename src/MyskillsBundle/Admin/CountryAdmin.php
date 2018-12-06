<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\Entity\Country;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class CountryAdmin extends Admin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('ruTitle', 'text', array('label' => 'ruTitle', 'required' => true))
            ->add('enTitle', 'text', array('label' => 'enTitle', 'required' => false))
        ;
    }

    public function configureListFields( ListMapper $list )
    {
        $list
            ->addIdentifier('ruTitle')
            ->addIdentifier('enTitle');
    }

    public function configureDatagridFilters( DatagridMapper $filter )
    {
        $filter
            ->add('ruTitle')
            ->add('enTitle');
    }

    public function toString( $object )
    {
        return $object instanceof Country
            ? $object->getRuTitle()
            : 'Country';
    }
}