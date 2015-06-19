<?php

namespace Civi\Cxn\AppBundle\Controller;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

  /**
   * @var AppStoreInterface
   */
  private $appStore;

  /**
   * @var CxnStoreInterface
   */
  private $cxnStore;

  public function __construct(ContainerInterface $container, AppStoreInterface $appStore, CxnStoreInterface $cxnStore) {
    $this->setContainer($container);
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
  }

  public function indexAction() {
    $out = '';

    foreach ($this->appStore->getAppIds() as $appId) {
      $appMeta = $this->appStore->getAppMeta($appId);
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
    // Some app has to sign the response. We'll arbitrarily pick one (the first).
    $firstAppId = NULL;

    $appMetas = array();
    foreach ($this->appStore->getAppIds() as $appId) {
      if ($firstAppId === NULL) {
        $firstAppId = $appId;
      }
      $appMetas[$appId] = $this->appStore->getAppMeta($appId);
    }

    $message = new \Civi\Cxn\Rpc\Message\AppMetasMessage(
      $appMetas[$firstAppId]['appCert'],
      $this->appStore->getKeyPair($firstAppId),
      $appMetas
    );
    return $message->toSymfonyResponse();
  }

}
