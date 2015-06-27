<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\RegistrationServer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class AppRegistrationServer extends RegistrationServer {

  // WISHLIST: Override onCxnRegister and onCxnUnregister; have them dispatch events
  // for the benefit of other bundles.

  /**
   * @var Router
   */
  protected $router;

  /**
   * Compose a secure link to a settings page.
   *
   * @param $cxn
   * @param $params
   * @return array
   */
  public function onCxnGetlink($cxn, $params) {
    $storedCxn = $this->cxnStore->getByCxnId($cxn['cxnId']);

    if (!$storedCxn || $storedCxn['secret'] !== $cxn['secret']) {
      return $this->createError('"cxnId" or "secret" is invalid.');
    }

    if (empty($params['page']) || !preg_match('/^[a-zA-Z0-9_]$/', $params['page'])) {
      return $this->createError('"page" is malformed.');
    }

    $cxnToken = 'FIXME'; // TODO: createCxnToken
    $routeName = 'org_civicrm_cron_settings'; // TODO: route(munge($appId) _ page)

    $this->log->notice('Getlink cxnId="{cxnId}" siteUrl={siteUrl} route={routeName}: OK', array(
      'cxnId' => $cxn['cxnId'],
      'siteUrl' => $cxn['siteUrl'],
      'routeName' => $routeName,
    ));

    return $this->createSuccess(array(
      'cxn_id' => $cxn['cxnId'],
      'url' => $this->router->generate(
        $routeName,
        array(
          'appId' => $storedCxn['appId'],
          'cxnToken' => $cxnToken,
        ),
        UrlGeneratorInterface::ABSOLUTE_URL
      ),
    ));
  }

  /**
   * @return Router
   */
  public function getRouter() {
    return $this->router;
  }

  /**
   * @param Router $router
   */
  public function setRouter($router) {
    $this->router = $router;
  }


}
