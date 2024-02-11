<?php
namespace Fontai\Bundle\FccBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class FccTaskType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $commonMessage = 'Pomozte nám prosím lépe identifikovat místo problému, výrazně tím urychlíte jeho vyřešení. Děkuji Lily.';

    $builder->add('subject', Type\TextType::class, [
      'label'       => 'Předmět žádosti',
      'required'    => TRUE,
      'attr'        => [
        'placeholder' => 'Předmět žádosti'
      ],
      'constraints' => [
        new Constraints\NotBlank()
      ]
    ]);

    $builder->add('text', Type\TextareaType::class, [
      'label'       => 'Popište co se nedaří',
      'required'    => TRUE,
      'attr'        => [
        'placeholder' => 'Popište co se nedaří'
      ],
      'constraints' => [
        new Constraints\NotBlank()
      ]
    ]);

    $builder->add('link', Type\TextType::class, [
      'label'       => 'Odkaz, kde se problém vyskytl',
      'required'    => FALSE,
      'attr'        => [
        'placeholder' => 'Odkaz, kde se problém vyskytl'
      ],
      'constraints' => [
        new Constraints\Url()
        //Zadejte odkaz nebo přidejte přílohu
      ]
    ]);

    $builder->add('attachment', Type\CollectionType::class, [
      'entry_type'  => Type\HiddenType::class,
      'allow_add'   => TRUE
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'name' => 'fcc_task'
    ]);

    $resolver->setRequired([
      'action'
    ]);
  }
}
