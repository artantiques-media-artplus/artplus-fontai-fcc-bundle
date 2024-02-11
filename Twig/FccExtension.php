<?php
namespace Fontai\Bundle\FccBundle\Twig;

use Fontai\Bundle\FccBundle\Service\Fcc;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class FccExtension extends AbstractExtension
{
  protected $fcc;

  public function __construct(Fcc $fcc)
  {
    $this->fcc = $fcc;
  }

  public function getFunctions()
  {
    return [
      new TwigFunction(
        'fcc_form',
        [$this, 'getForm'],
        [
          'needs_environment' => TRUE,
          'is_safe'           => ['html']
        ]
      ),
      new TwigFunction(
        'fcc_task_list',
        [$this, 'getTaskList'],
        [
          'needs_environment' => TRUE,
          'is_safe'           => ['html']
        ]
      )
    ];
  }

  public function getForm(\Twig_Environment $environment)
  {
    return $environment->render(
      '@Fcc/form.html.twig',
      [
        'form_task' => $this->fcc->getTaskForm()->createView(),
        'form_upload' => $this->fcc->getUploadForm()->createView()
      ]
    );
  }

  public function getTaskList(\Twig_Environment $environment, $done = FALSE)
  {
    $data = $this->fcc->getTasks($done);

    return $environment->render('@Fcc/task_list.html.twig', $data);
  }

  public function getName()
  {
    return 'fcc';
  }
}
