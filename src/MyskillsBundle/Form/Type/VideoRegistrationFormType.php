<?php

namespace MyskillsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoRegistrationFormType extends AbstractType
{
    private $class;

    /**
     * @var array
     */
    protected $mergeOptions;

    /**
     * @param string $class        The User class name
     * @param array  $mergeOptions Add options to elements
     */
    public function __construct($class, array $mergeOptions = array())
    {
        $this->class        = $class;
        $this->mergeOptions = $mergeOptions;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, array_merge(array(
                'label' => 'form.username',
                'translation_domain' => 'SonataUserBundle',
            ), $this->mergeOptions))
            ->add('email', 'email', array_merge(array(
                'label' => 'form.email',
                'translation_domain' => 'SonataUserBundle',
            ), $this->mergeOptions))
            ->add('plainPassword', 'repeated', array_merge(array(
                'type' => 'password',
                'options' => array('translation_domain' => 'SonataUserBundle'),
                'first_options' => array_merge(array(
                    'label' => 'form.password',
                ), $this->mergeOptions),
                'second_options' => array_merge(array(
                    'label' => 'form.password_confirmation',
                ), $this->mergeOptions),
                'invalid_message' => 'fos_user.password.mismatch',
            ), $this->mergeOptions))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
        ));
    }

    public function getName()
    {
        return 'video_user_registration';
    }
}
