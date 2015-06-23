<?php
namespace Civi\Cxn\DirBundle\AppStore;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Class JsonAppStore
 * @package Civi\Cxn\DirBundle
 *
 * Store application metadata in a single JSON file.
 * Note: This does NOT support private keys.
 */
class JsonAppStore implements AppStoreInterface {

  /**
   * @var string
   */
  protected $jsonFile;

  /**
   * @var array|NULL
   *   Array (string $appId => array $appMeta).
   */
  protected $appMetas = NULL;

  /**
   * @param string $jsonFile
   *   Path to the JSON file.
   */
  public function __construct($jsonFile) {
    $this->jsonFile = $jsonFile;
  }

  /**
   * @param bool $fresh
   * @return array
   *   Array (string $appId => array $appMeta).
   */
  protected function getAll($fresh = FALSE) {
    if ($fresh || $this->appMetas === NULL) {
      if (file_exists($this->jsonFile)) {
        $this->appMetas = json_decode(file_get_contents($this->jsonFile), TRUE);
      }
      else {
        $this->appMetas = array();
      }
    }
    return $this->appMetas;
  }

  /**
   * @return array
   *   List of App IDs.
   */
  public function getAppIds() {
    return array_keys($this->getAll());
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   The application metadata.
   * @see AppMeta
   */
  public function getAppMeta($appId) {
    return (isset($this->appMetas[$appId]) ? $this->appMetas[$appId] : NULL);
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPublicKey($appId) {
    $appMeta = $this->getAppMeta($appId);
    return $appMeta['appCert'];
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPrivateKey($appId) {
    throw new \RuntimeException(__CLASS__ . " does not support getPrivateKey");
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
    return array(
      'publickey' => $this->getPublicKey($appId),
    );
  }

}
