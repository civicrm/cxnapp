<?php
namespace Civi\Cxn\DirBundle\AppStore;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Class ChainedAppStore
 * @package Civi\Cxn\DirBundle\AppStore
 *
 * Aggregate the list of apps from other sources.
 */
class ChainedAppStore implements AppStoreInterface {

  /**
   * @var array
   */
  protected $appStores;

  /**
   * @var array
   *   Array (string $appId => scalar $appStoreId).
   */
  protected $idMap;

  function __construct($appStores) {
    $this->appStores = $appStores;
  }

  protected function getIdMap() {
    if ($this->idMap === NULL) {
      $this->idMap = array();
      foreach ($this->appStores as $appStoreId => $appStore) {
        foreach ($appStore->getAppIds() as $appId) {
          $this->idMap[$appId] = $appStoreId;
        }
      }
    }
    return $this->idMap;
  }

  /**
   * Determine which AppStore contains $appId.
   *
   * @param string $appId
   * @return AppStoreInterface
   * @throws \RuntimeException
   */
  protected function getAppStore($appId) {
    $map = $this->getIdMap();
    if (isset($map[$appId])) {
      return $this->appStores[$map[$appId]];
    }
    else {
      throw new \RuntimeExcpetion("Unrecognized AppID");
    }
  }

  /**
   * @return array
   *   List of App IDs.
   */
  public function getAppIds() {
    $map = $this->getIdMap();
    return array_keys($map);
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   The application metadata.
   * @see AppMeta
   */
  public function getAppMeta($appId) {
    return $this->getAppStore($appId)->getAppMeta($appId);
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPublicKey($appId) {
    return $this->getAppStore($appId)->getPublicKey($appId);
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPrivateKey($appId) {
    return $this->getAppStore($appId)->getPrivateKey($appId);
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   Array with elements:
   *     - publickey: string, pem.
   *     - privatekey: string, pem
   */
  public function getKeyPair($appId) {
    return $this->getAppStore($appId)->getKeyPair($appId);
  }

}
