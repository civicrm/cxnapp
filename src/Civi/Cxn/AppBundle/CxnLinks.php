<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\AppBundle\Entity\CxnToken;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class CxnLinks {

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

  const TTL = '+2 hours';

  public function __construct(Router $router, LoggerInterface $logger, EntityManager $em) {
    $this->router = $router;
    $this->log = $logger;
    $this->em = $em;
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
   * @return string
   */
  public function generate($cxn, $params) {
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
        'cxnToken' => $cxnToken,
      ),
      UrlGeneratorInterface::ABSOLUTE_URL
    );
    return $url;
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
    $cxnToken = rtrim(strtr(base64_encode(crypt_random_string(32)), '+/', '-_'), '=');

    $cxnTokenEntity = new CxnToken();
    $cxnTokenEntity->setCxnId($cxn['cxnId']);
    $cxnTokenEntity->setCxnToken($cxnToken);
    $cxnTokenEntity->setExpires(new \DateTime(self::TTL));
    $cxnTokenEntity->setPage($params['page']);
    $this->em->persist($cxnTokenEntity);
    $this->em->flush($cxnTokenEntity);

    return $cxnToken;
  }

  public function cleanup() {
    $this->em->createQuery('DELETE FROM Civi\Cxn\AppBundle\Entity\CxnToken ct WHERE ct.expires < CURRENT_TIMESTAMP()')
      ->execute();
  }

}
