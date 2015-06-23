<?php

namespace Civi\Cxn\DirBundle\Controller;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
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

  public function __construct(ContainerInterface $container, AppStoreInterface $appStore, EventDispatcherInterface $eventDispatcher) {
    $this->setContainer($container);
    $this->appStore = $appStore;
    $this->eventDispatcher = $eventDispatcher;
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

    // Some keypair has to sign the response. We'll arbitrarily pick the first app's keypair.
    // FIXME: Use the dirsvc's keypair.
    $firstApp = NULL;
    foreach ($appMetas as $appMeta) {
      $firstApp = $appMeta;
      break;
    }

    $message = new \Civi\Cxn\Rpc\Message\AppMetasMessage(
      $firstApp['appCert'],
      $this->appStore->getKeyPair($firstApp['appId']),
      $appMetas
    );
    return $message->toSymfonyResponse();
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
