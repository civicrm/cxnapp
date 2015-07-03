<?php

namespace Civi\Cxn\CronBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CronSettingsType extends AbstractType {
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('email', NULL, array(
        'label' => 'Administrative Email Address',
        'required' => FALSE,
      ))
      ->add('save', 'submit', array(
        'label' => 'Save',
      ));
  }

  /**
   * @param OptionsResolverInterface $resolver
   */
  public function setDefaultOptions(OptionsResolverInterface $resolver) {
    $resolver->setDefaults(array(
      'data_class' => 'Civi\Cxn\CronBundle\Entity\CronSettings',
    ));
  }

  /**
   * @return string
   */
  public function getName() {
    return 'civi_cxn_cronbundle_cronsettings';
  }

}
