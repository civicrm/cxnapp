<?php

namespace Civi\Cxn\DirBundle\Controller;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class DirSvcController extends Controller {

  /**
   * @var AppStoreInterface
   */
  private $appStore;

  /**
   * @var EventDispatcherInterface
   */
  private $eventDispatcher;

  private $keyFile;

  private $certFile;

  private $maxAge;

  public function __construct(ContainerInterface $container, AppStoreInterface $appStore, EventDispatcherInterface $eventDispatcher, $keyFile, $certFile, $maxAge) {
    $this->setContainer($container);
    $this->appStore = $appStore;
    $this->eventDispatcher = $eventDispatcher;
    $this->keyFile = $keyFile;
    $this->certFile = $certFile;
    $this->maxAge = $maxAge;
  }

  public function indexAction() {
    $appMetas = $this->convertAppStoreToArray($this->appStore);
    $out = '';
    foreach ($appMetas as $appMeta) {
      $out .= sprintf("== %s (%s) ==\n\n%s\n\n", $appMeta['title'], $appMeta['appId'], $appMeta['desc']);
    }

    return new Response($out, 200, array(
      'Content-type' => 'text/plain',
    ));
  }

  /**
   * Simulate a directory service which knows about the
   * applications on this particular server.
   *
   * @return Response
   */
  public function appsAction() {
    $appMetas = $this->convertAppStoreToArray($this->appStore);

    $message = new \Civi\Cxn\Rpc\Message\AppMetasMessage(
      file_get_contents($this->certFile),
      KeyPair::load($this->keyFile),
      $appMetas
    );
    $response = $message->toSymfonyResponse();
    $response->setMaxAge($this->maxAge);
    $response->setPublic();
    return $response;
  }

  /**
   * @return array
   */
  protected function convertAppStoreToArray(AppStoreInterface $appStore) {
    $appMetas = array();
    foreach ($appStore->getAppIds() as $appId) {
      $appMetas[$appId] = $appStore->getAppMeta($appId);
    }
    return $appMetas;
  }

}
