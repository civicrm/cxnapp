<?php

namespace Civi\Cxn\AppBundle\Controller;

use Civi\Cxn\AppBundle\AppRegistrationServer;
use Civi\Cxn\AppBundle\CxnLinks;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CxnAppController
 * @package Civi\Cxn\AppBundle\Controller
 *
 * This controller defines all the endpoints for an
 * individual CxnApp.
 */
class CxnAppController extends Controller {

  /**
   * @var AppStoreInterface
   */
  private $appStore;

  /**
   * @var CxnStoreInterface
   */
  private $cxnStore;

  /**
   * @var LoggerInterface
   */
  protected $log;

  /**
   * @var CxnLinks
   */
  private $cxnLinks;

  public function __construct(ContainerInterface $container, AppStoreInterface $appStore, CxnStoreInterface $cxnStore, LoggerInterface $log, CxnLinks $cxnLinks) {
    $this->setContainer($container);
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
    $this->log = $log;
    $this->cxnLinks = $cxnLinks;
  }

  /**
   * Simulate a directory service which knows about only
   * one application (ie $appId).
   *
   * @param string $appId
   * @return Response
   */
  public function appsAction($appId) {
    $appMeta = $this->appStore->getAppMeta($appId);
    $message = new \Civi\Cxn\Rpc\Message\AppMetasMessage(
      $appMeta['appCert'],
      $this->appStore->getKeyPair($appId),
      array($appId => $appMeta)
    );
    return $message->toSymfonyResponse();
  }

  /**
   * Get just the metadata about this application.
   *
   * @param string $appId
   * @return Response
   */
  public function metadataAction($appId) {
    return new Response(
      json_encode($this->appStore->getAppMeta($appId)),
      200,
      array('Content-Type' => 'application/javascript')
    );
  }

  /**
   * Process requests to register/unregister.
   *
   * @param string $appId
   * @return Response
   */
  public function registerAction($appId) {
    $appMeta = $this->appStore->getAppMeta($appId);
    $server = new AppRegistrationServer($appMeta, $this->appStore->getKeyPair($appId), $this->cxnStore);
    $server->setLog($this->log);
    $server->setCxnLinks($this->cxnLinks);
    return $server->handle(file_get_contents('php://input'))->toSymfonyResponse();
  }

}
