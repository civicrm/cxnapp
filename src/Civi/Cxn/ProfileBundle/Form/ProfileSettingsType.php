<?php

namespace Civi\Cxn\ProfileBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileSettingsType extends AbstractType {
  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('expires', NULL, array(
        'label' => 'Expiration',
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
      'data_class' => 'Civi\Cxn\ProfileBundle\Entity\ProfileSettings',
    ));
  }

  /**
   * @return string
   */
  public function getName() {
    return 'civi_cxn_profilebundle_profilesettings';
  }

}
