<?php
namespace Fontai\Bundle\FccBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class FccUploadType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('file', Type\FileType::class, [
      'label'       => 'Připojit soubor',
      'required'    => TRUE,
      'attr'        => [
        'placeholder' => 'Připojit soubor'
      ],
      'constraints' => [
        new Constraints\NotBlank()
      ]
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'name' => 'fcc_upload'
    ]);

    $resolver->setRequired([
      'action'
    ]);
  }
}
