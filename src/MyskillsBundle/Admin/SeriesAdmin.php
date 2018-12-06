<?php
namespace MyskillsBundle\Admin;

use MyskillsBundle\Entity\Series;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use MyskillsBundle\Entity\Video;

class SeriesAdmin extends Admin
{
    private $videoAdmin;

    public function postRemove( $project )
    {
        $this->postUpdate($project);
    }

    public function postPersist( $project )
    {
        $this->postUpdate($project);
    }

    public function postUpdate($project)
    {
        if(!is_null($project) && $project->getEpisodesCnt()) {
            $episodes = $project->getEpisodes();
            /** @var Video $episode */
            foreach($episodes as $episode) {
                $this->videoAdmin->update($episode);
            }
        }
    }

    /**
     * @param VideoAdmin $videoAdmin
     */
    public function setVideoAdmin( VideoAdmin $videoAdmin )
    {
        $this->videoAdmin = $videoAdmin;
    }

    protected $datagridValues = array(

        // display the first page (default = 1)
        '_page' => 1,

        // reverse order (default = 'ASC')
        '_sort_order' => 'DESC',

        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'timeAdd',
    );

    public function configureFormFields( FormMapper $form )
    {
        $form
            ->add('poster', 'sonata_type_model_list', array(), array('link_parameters' => array('context' => 'default')))
            ->add('code', 'text', array('label' => 'символьный код', 'required' => true))
            ->add('type', 'choice', array(
                'choices' => Series::SERIES_TYPES
            ))
            ->add('ruTitle', 'text', array('label' => 'ruTitle', 'required' => false))
            ->add('enTitle', 'text', array('label' => 'enTitle', 'required' => true))
            ->add('ruDescription', 'textarea', array('required' => false))
            ->add('enDescription', 'textarea', array('required' => false))
            ->add('isPublic')
            ->add('datePublish', 'date', array('years' => range(1900, date('Y')), 'label' => 'дата публикации', 'required' => false))
            ->add('startYear', 'integer', array('required' => false))
            ->add('finishYear', 'integer', array('required' => false))
            ->add('episodesCnt', null, array('read_only' => true, 'disabled'  => true, 'label' => 'кол-во эпизодов'))
            ->add('number', null, array('required' => true, 'label' => 'сортировка вывода (по возрастанию)'))
            ->add('genres')
            ->add('tags')
            ->add('countries');

    }

    public function configureListFields( ListMapper $list )
    {
        $list
            ->addIdentifier('poster', '', array('label' => 'Картинка', 'template' => 'MyskillsBundle:Admin:material.image.html.twig'))
            ->addIdentifier('enTitle')
            ->addIdentifier('ruTitle')
            ->add('type')
            ->add('episodesCnt', '', array('label' => 'Кол-во эпизодов'))
            ->add('isPublic', 'boolean', array('editable' => true));
    }

    public function configureDatagridFilters( DatagridMapper $filter )
    {
        $filter
            ->add('isPublic')
            ->add('ruTitle', 'doctrine_orm_callback', array(
                'callback' => array($this, 'getFullTextFilter'),
                'field_type' => 'text'
            ))
            ->add('type', 'doctrine_orm_string', array(),
                'choice', array('choices' => Series::SERIES_TYPES)
            );
    }

    public function toString( $object )
    {
        return $object instanceof Series
            ? $object->getRuTitle()
            : 'Series';
    }

    public function getFullTextFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }
        $queryBuilder->where($queryBuilder->expr()->like($alias.'.ruTitle', $queryBuilder->expr()->literal($value['value'] . '%')));
        return true;
    }
}