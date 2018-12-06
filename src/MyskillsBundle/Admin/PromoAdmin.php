<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\Entity\Promo;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class PromoAdmin extends Admin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('code', null, array('read_only' => true, 'disabled'  => true, 'label' => 'Промо-код'))
            ->add('discountPercent', 'text', array('label' => 'Размер скидки, %', 'required' => true))
            ->add('comment')
            ->add('isActive')
        ;
    }

    public function configureListFields( ListMapper $list )
    {
        $list
            ->addIdentifier('code')
            ->add('discountPercent', 'integer', array('label'=>'Скидка, %', 'editable' => true))
            ->add('idUser', 'integer', array('editable' => true))
            ->add('isPayment', 'boolean', array('label'=>'Использован', 'editable' => true))
            ->add('isActive', 'boolean', array('editable' => true));
    }

    public function configureDatagridFilters( DatagridMapper $filter )
    {
        $filter
            ->add('code')
            ->add('discountPercent')
            ->add('idUser')
            ->add('isPayment')
            ->add('isActive');
    }

    public function toString( $object )
    {
        return $object instanceof Promo
            ? $object->getCode()
            : 'Promo';
    }
}