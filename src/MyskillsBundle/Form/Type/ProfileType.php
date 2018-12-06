<?php

namespace MyskillsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Sonata\UserBundle\Model\UserInterface;

class ProfileType extends AbstractType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gender', 'sonata_user_gender', array(
                'label'    => 'form.label_gender',
                'required' => true,
                'translation_domain' => 'SonataUserBundle',
                'choices' => array(
                    UserInterface::GENDER_UNKNOWN => 'gender_unknown',
                    UserInterface::GENDER_FEMALE => 'gender_female',
                    UserInterface::GENDER_MALE   => 'gender_male',
                )
            ))
            ->add('firstname', null, array(
                'label'    => 'form.label_firstname',
                'required' => false
            ))
            ->add('lastname', null, array(
                'label'    => 'form.label_lastname',
                'required' => false
            ))
            ->add('dateOfBirth', 'birthday', array(
                'label'    => 'form.label_date_of_birth',
                'required' => false,
                'format' => 'dd.MM.yyyy',
            ))
            ->add('website', 'url', array(
                'label'    => 'form.label_website',
                'required' => false,
            ))
            ->add('biography', 'textarea', array(
                'label'    => 'form.label_biography',
                'required' => false
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'video_user_profile';
    }
}
