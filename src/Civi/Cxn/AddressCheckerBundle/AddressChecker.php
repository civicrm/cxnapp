<?php

namespace Civi\Cxn\AddressCheckerBundle;

use Civi\Cxn\AddressCheckerBundle\Entity\AddrCache;
use Civi\Cxn\Rpc\Time;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddressChecker {

  /**
   * @var Cache
   */
  private $cache;

  /**
   * @var int seconds
   */
  private $socketTimeout;

  /**
   * @var int seconds
   *
   * Half hour.
   */
  private $cacheTtl;

  /**
   * AddressChecker constructor.
   * @param Cache $cache
   * @param int $socketTimeout
   * @param int $cacheTtl
   */
  public function __construct(Cache $cache, $socketTimeout = 1, $cacheTtl = 1800) {
    $this->cache = $cache;
    $this->socketTimeout = $socketTimeout;
    $this->cacheTtl = $cacheTtl;
  }

  /**
   * Determine if an address is routeable on the public Internet.
   *
   * @param string $url
   * @return string
   *   'ok' or else an error code.
   */
  public function checkUrl($url) {
    if (strlen($url) > 255) {
      return 'bad-length';
    }

    $schemes = array(
      'http' => 80,
      'https' => 443,
    );

    $scheme = parse_url($url, PHP_URL_SCHEME);
    $host = parse_url($url, PHP_URL_HOST);
    $port = parse_url($url, PHP_URL_PORT);
    $path = parse_url($url, PHP_URL_PATH);

    if (!isset($schemes[$scheme])) {
      return 'bad-scheme';
    }

    if (empty($port)) {
      $port = $schemes[$scheme];
    }

    if (!preg_match(':extern/cxn.php$:', $path)) {
      return 'bad-path';
    }

    if (preg_match('/^\[[0-9a-f:]+\]$/', $host)) {
      return 'no-ipv6';
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
      $ipv4 = $host;
    }
    else {
      $ipv4 = gethostbyname($host);
      if ($ipv4 === $host) {
        return 'bad-dns';
      }
    }

    if (!$this->checkPublicIp($ipv4)) {
      return 'private-ip';
    }

    if (!$this->checkIpPort($ipv4, $port)) {
      return 'bad-socket';
    }

    return 'ok';
  }

  public function checkPublicIp($ip) {
    list ($a, $b, $c, $d) = explode('.', $ip);
    if ($a == 127) {
      return FALSE;
    }
    if ($a == 10) {
      return FALSE;
    }
    if ($a == 192 && $b == 168) {
      return FALSE;
    }
    if ($a == 172 && $b >= 16 && $b <= 31) {
      return FALSE;
    }
    if ($a == 169 && $b == 254) {
      return FALSE;
    }
    return TRUE;
  }

  public function checkIpPort($ip, $port) {
    $cacheKey = "[$ip]:$port";
    if ($this->cache->contains($cacheKey)) {
      return $this->cache->fetch($cacheKey);
    }

    $fp = @fsockopen($ip, $port, $errno, $errstr, $this->socketTimeout);
    $result = $fp ? TRUE : FALSE;
    @fclose($fp);

    $this->cache->save($cacheKey, $result, $this->getCacheTtl());
    return $result;
  }

  /**
   * @return Cache
   */
  public function getCache() {
    return $this->cache;
  }

  /**
   * @param Cache $cache
   * @return $this
   */
  public function setCache($cache) {
    $this->cache = $cache;
    return $this;
  }

  /**
   * @return int
   */
  public function getSocketTimeout() {
    return $this->socketTimeout;
  }

  /**
   * @param int $socketTimeout
   */
  public function setSocketTimeout($socketTimeout) {
    $this->socketTimeout = $socketTimeout;
  }

  /**
   * @return int
   */
  public function getCacheTtl() {
    return $this->cacheTtl;
  }

  /**
   * @param int $cacheTtl
   */
  public function setCacheTtl($cacheTtl) {
    $this->cacheTtl = $cacheTtl;
  }

}
