<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\RegistrationServer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class AppRegistrationServer extends RegistrationServer {

  // WISHLIST: Override onCxnRegister and onCxnUnregister; have them dispatch events
  // for the benefit of other bundles.

  /**
   * @var CxnLinks
   */
  protected $cxnLinks;

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

    try {
      return $this->createSuccess($this->getCxnLinks()->generate($storedCxn, $params));
    }
    catch (\InvalidArgumentException $e) {
      return $this->createError($e->getMessage());
    }
  }

  /**
   * @return CxnLinks
   */
  public function getCxnLinks() {
    return $this->cxnLinks;
  }

  /**
   * @param CxnLinks $cxnLinks
   */
  public function setCxnLinks($cxnLinks) {
    $this->cxnLinks = $cxnLinks;
  }

}
