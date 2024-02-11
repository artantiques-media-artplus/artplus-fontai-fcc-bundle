<?php
namespace Fontai\Bundle\FccBundle\Controller;

use App\Model;
use Fontai\Bundle\FccBundle\Service\Fcc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class FccController extends AbstractController
{
  public function tasks(
    Request $request,
    Fcc $fcc
  )
  {
    $data = $fcc->getTasks(
      (bool) $request->query->get('done'),
      intval($request->query->get('page'))
    );

    return $this->render('@Fcc/task_list.html.twig', $data);
  }

  public function task(
    Request $request,
    Fcc $fcc,
    TranslatorInterface $translator    
  )
  {
    $projectId = $request->getSession()->get('projectId');

    if ($projectId && ($project = Model\ProjectQuery::create()->findOneById($projectId)))
    {
      $form = $fcc
      ->getTaskForm()
      ->handleRequest($request);

      if ($form->isSubmitted())
      {
        if (!$form->isValid())
        {
          return $this->parseErrors('Chyba v přidávání!', $form);
        }

        $data = $form->getData();

        foreach ($data['attachment'] as &$attachment)
        {
          $attachment = $this->generateUrl(
            'fcc_attachment',
            ['filename' => $attachment],
            UrlGeneratorInterface::ABSOLUTE_URL
          );
        }

        $result = $fcc->addTask([
          'name'        => $data['subject'],
          'description' => $data['text'],
          'link'        => $data['link'],
          'files'       => $data['attachment']
        ]);

        if ($result === TRUE)
        {
          return $this->json([
            'success' => TRUE,
            'title'   => $translator->trans('Zadání proběhlo v pořádku!')
          ]);
        }
        
        return $this->json([
          'success' => FALSE,
          'title'   => $translator->trans('Chyba v přidávání!') . ' ' . $result
        ]);
      }
    }
  }

  public function upload(
    Request $request,
    Fcc $fcc,
    Filesystem $filesystem,
    TranslatorInterface $translator    
  )
  {
    $form = $fcc
    ->getUploadForm()
    ->handleRequest($request);

    $filename = NULL;
      
    if ($form->isSubmitted() && $form->isValid())
    {
      $cacheDir = $fcc->getCacheDir();

      $filesystem->mkdir($cacheDir, 0777);
      $this->clearCache($cacheDir);

      $file = $form['file']->getData();

      $filename = sprintf('%s_%s', bin2hex(random_bytes(10)), $file->getClientOriginalName());

      $file->move($cacheDir, $filename);
    }

    return $this->json([
      'filename' => $filename
    ]);
  }

  public function attachment(
    Request $request,
    Fcc $fcc,
    string $filename
  )
  {
    $cacheDir = $fcc->getCacheDir();
    $path = $cacheDir . $filename;

    if (!is_file($path))
    {
      throw $this->createNotFoundException();
    }

    return $this->file($path);
  }

  protected function clearCache($path)
  {
    $now = time();

    foreach (new \DirectoryIterator($path) as $file)
    {
      if ($file->isFile() && ($file->getMTime() + (60 * 60)) < $now) unlink($file->getPathname());
    }
  }

  protected function parseErrors($title = 'Položku nelze uložit', $form = NULL)
  {
    $translator = $this->get('translator');

    $r = [
      'success' => FALSE,
      'title'   => $translator->trans($title),
      'errors'  => []
    ];

    if ($form)
    {
      foreach ($form->getErrors(TRUE) as $i => $error)
      {
        $origin = $error->getOrigin();
        $name   = sprintf('[%s]', $origin->getName());
        $parent = $origin->getParent();
    
        if (!$parent)
        {
          $name = NULL;
        }
        else
        {
          do
          {
            $parentName = $parent->getName();

            if ($parent = $parent->getParent())
            {
              $parentName = sprintf('[%s]', $parentName);
            }

            $name = $parentName . $name;
          }
          while ($parent);
        }
        
        $r['errors'][$name] = [
          $translator->trans($origin->getConfig()->getOption('label')),
          $translator->trans($error->getMessage())
        ];
      }
    }

    return $this->json($r);
  }
}