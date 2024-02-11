<?php
namespace Fontai\Bundle\FccBundle\Service;

use App\Model;
use Fontai\Bundle\FccBundle\Form\FccTaskType;
use Fontai\Bundle\FccBundle\Form\FccUploadType;
use GuzzleHttp\Client;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;


class Fcc
{
  protected $formFactory;
  protected $router;
  protected $url;
  protected $client;

  protected $projectId;
  protected $appKey;
  protected $appPass;
  protected $userName;

  public function __construct(
    FormFactoryInterface $formFactory,
    RouterInterface $router,
    string $cacheDir,
    string $url
  )
  {
    $this->formFactory  = $formFactory;
    $this->router       = $router;
    $this->cacheDir     = sprintf('%s/fcc/', $cacheDir);
    $this->url          = $url;
  
    $this->client = new Client([
      'base_uri' => $this->url . '/api/',
      'timeout'  => 5,
      'verify' => false
    ]);
  }

  public function addTask($data)
  {
    $data['reported_by'] = $this->userName;

    try
    {
      $res = $this->client->post(
        'addTask',
        [
          'form_params' => [
            'hash'    => sha1(md5($this->projectId . ':' . sha1($this->appPass) . ':' . $this->appKey)),
            'project' => $this->projectId,
            'data'    => $data
          ]
        ]
      );

      $r      = json_decode($res->getBody(), TRUE);
      $status = explode('::', $r['output']);

      if ($status[0] == 'OK')
      {
        return TRUE;
      }
      else
      {
        return isset($status[1]) ? $status[1] : $data;
      }
    }
    catch (\Exception $e)
    {
      return FALSE;
    }
  }

  public function setProjectId($v)
  {
    $this->projectId = $v;
    return $this;
  }

  public function setAppKey($v)
  {
    $this->appKey = $v;
    return $this;
  }

  public function setAppPass($v)
  {
    $this->appPass = $v;
    return $this;
  }

  public function setUserName($v)
  {
    $this->userName = $v;
    return $this;
  }

  public function getTaskForm()
  {
    $form = $this->formFactory->create(
      FccTaskType::class,
      [],
      [
        'action' => $this->router->generate('fcc_task')
      ]
    );

    return $form;
  }

  public function getUploadForm()
  {
    $form = $this->formFactory->create(
      FccUploadType::class,
      [],
      [
        'action' => $this->router->generate('fcc_upload')
      ]
    );

    return $form;
  }

  public function getTasks($done = FALSE, $page = 1)
  {
    $perPage = 10;

    $data = [
      'done'      => $done,
      'count'     => 0,
      'page'      => 1,
      'startPage' => 1,
      'endPage'   => 1,
      'maxPage'   => 1,
      'tasks'     => []
    ];
  
    try
    {
      $res = $this->client->post(
        'listTask',
        [
          'form_params' => [
            'hash'    => sha1(md5($this->projectId . ':' . sha1($this->appPass) . ':' . $this->appKey)),
            'project' => $this->projectId,
            'count'   => $perPage,
            'offset'  => ($page - 1) * $perPage,
            'done'    => $done,
            'gzip'    => TRUE
          ]
        ]
      );

      $r = json_decode($res->getBody(), TRUE);

      if ($r['output'] != 'OK::00')
      {
        return $data;
      }

      $data['count'] = $r['count'];

      unset($r['output'], $r['count']);
      $tasks = is_array($r) ? array_values($r) : [];
    }
    catch (\Exception $e)
    {
      return $data;
    }

    $data['page'] = $page;
    $data['maxPage'] = ceil($data['count'] / $perPage);

    $data['startPage'] = max($page - ($page == $data['maxPage'] ? 2 : 1), 1);

    $data['endPage'] = min($page + ($page == 1 ? 2 : 1), $data['maxPage']);

    for ($i = 0; $i < $perPage; $i++)
    {
      if (isset($tasks[$i]))
      {
        $data['tasks'][] = $tasks[$i];
      }
    }

    return $data;
  }

  public function getCacheDir()
  {
    return $this->cacheDir;
  }
}