<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\Cxn;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class CxnLinks {

  /**
   * @var array
   *
   * Default options for each link -- e.g. 'mode', 'width', 'height'
   */
  protected $defaults;

  /**
   * @var Router
   */
  protected $router;

  /**
   * @var LoggerInterface
   */
  protected $log;

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var string
   */
  protected $secret;

  protected $ttl;

  public function __construct(Router $router, LoggerInterface $logger, EntityManager $em, $secret, $ttl, $defaults) {
    $this->router = $router;
    $this->log = $logger;
    $this->em = $em;
    $this->secret = $secret;
    $this->ttl = $ttl;
    $this->defaults = $defaults;
  }

  /**
   * @param array $params
   * @return bool
   */
  public function validate($params) {
    return !empty($params['page']) && preg_match('/^[a-zA-Z0-9_]+$/', $params['page']);
  }

  /**
   * Generate an authenticated link to an application page.
   *
   * @param array $cxn
   * @param array $params
   *   Array(page => string).
   * @return array
   *   Array with keys:
   *     - mode: string
   *     - url: string
   */
  public function generate($cxn, $params) {
    if (!$this->validate($params)) {
      throw new \InvalidArgumentException("Failed to validate link request");
    }

    $cxnToken = $this->createToken($cxn, $params);
    $routeName = $this->mungeRoute($cxn['appId'], $params['page']);

    $this->log->notice('Getlink cxnId="{cxnId}" siteUrl={siteUrl} route={routeName}: OK', array(
      'cxnId' => $cxn['cxnId'],
      'siteUrl' => $cxn['siteUrl'],
      'routeName' => $routeName,
    ));

    $url = $this->router->generate(
      $routeName,
      array(
        'appId' => $cxn['appId'],
        'cxnId' => $cxn['cxnId'],
        'cxnToken' => $cxnToken,
      ),
      UrlGeneratorInterface::ABSOLUTE_URL
    );

    // TODO: Dispatch an event so that other bundles can manage links.

    return $this->defaults + array(
      'cxn_id' => $cxn['cxnId'],
      'url' => $url,
    );
  }

  /**
   * @param string $appId
   *   Ex: "app:org.civicrm.cron"
   * @param string $page
   *   Ex: "settings"
   * @return string
   *   Ex: "org_civicrm_cron_settings"
   */
  protected function mungeRoute($appId, $page) {
    $s = preg_replace('/^app:/', '', $appId) . '_' . $page;
    $s = preg_replace('/[^a-zA-Z0-9]/', '_', $s);
    return $s;
  }

  /**
   * @param array $cxn
   * @param array $params
   * @return string
   */
  protected function createToken($cxn, $params) {
    Cxn::validate($cxn);
    $expires = strtotime($this->ttl);
    $hash = hash_hmac('sha256', $expires . ';;;' . $cxn['cxnId'], $this->secret);
    return $hash . ';;;' . $expires;
  }

  /**
   * @param string $cxnId
   * @param string $cxnToken
   * @return FALSE|int
   *   FALSE if invalid. Otherwise, the timestamp at which it becomes invalid.
   */
  public function checkToken($cxnId, $cxnToken) {
    if (empty($cxnToken) || strpos($cxnToken, ';;;') === FALSE) {
      return FALSE;
    }

    list ($hash, $expires) = explode(';;;', $cxnToken);
    if (!self::isPositiveInt($expires)) {
      return FALSE;
    }

    $realHash = hash_hmac('sha256', $expires . ';;;' . $cxnId, $this->secret);
    if (!self::hash_compare($hash, $realHash)) {
      return FALSE;
    }

    return (int) $expires;
  }

  private static function hash_compare($a, $b) {
    if (!is_string($a) || !is_string($b)) {
      return FALSE;
    }

    $len = strlen($a);
    if ($len !== strlen($b)) {
      return FALSE;
    }

    $status = 0;
    for ($i = 0; $i < $len; $i++) {
      $status |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $status === 0;
  }

  /**
   * @param $expires
   * @return bool
   */
  protected static function isPositiveInt($expires) {
    return is_numeric($expires) && $expires > 0 && strpos($expires, '.') === FALSE;
  }

}
