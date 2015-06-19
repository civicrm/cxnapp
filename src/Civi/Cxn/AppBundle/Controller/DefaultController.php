<?php

namespace Civi\Cxn\AppBundle\Controller;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

  /**
   * @var AppStoreInterface
   */
  private $appStore;

  public function __construct(ContainerInterface $container, AppStoreInterface $appStore) {
    $this->setContainer($container);
    $this->appStore = $appStore;
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

    //return $this->render('CiviCxnAppBundle:Default:index.html.twig', array(
    //  'appMetas' => $appMetas,
    //));
  }

}
