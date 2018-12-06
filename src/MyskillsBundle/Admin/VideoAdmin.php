<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Service\VideoService;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class VideoAdmin extends Admin
{
    public function prePersist($video)
    {
        $this->preUpdate($video);
    }

    public function configureFormFields(FormMapper $form)
    {
        $form
            ->add('isPublic', null, ['label'=>'Опубликованный эпизод'])
            ->add('title', 'text', array('label' => 'Название на английском', 'required' => true))
            ->add('duration', 'number', array('label' => 'Длительность в секундах', 'required' => false));
    }

    public function configureListFields( ListMapper $list )
    {
        $list
            ->addIdentifier('thumb', '', array('label' => 'Картинка', 'template' => 'MyskillsBundle:Admin:material.image.html.twig'))
            ->addIdentifier('title');
    }

    public function configureDatagridFilters( DatagridMapper $filter )
    {
        $filter
            ->add('title', 'doctrine_orm_callback', array(
                'callback' => array($this, 'getFullTextFilter'),
                'field_type' => 'text'
            ));
    }

    public function toString( $object )
    {
        return $object instanceof Video
            ? $object->getTitle()
            : 'Video';
    }

    public function getFullTextFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }
        $queryBuilder->where($queryBuilder->expr()->like($alias.'.title', $queryBuilder->expr()->literal($value['value'] . '%')));
        return true;
    }
}