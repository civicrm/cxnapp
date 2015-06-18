<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\KeyPair;

/**
 * Class FileAppStore
 * @package Civi\Cxn\AppBundle
 *
 * Store application metadata in a series of files, e.g.,
 *
 * @code
 * app/cxn/org.civicrm.myapp/metadata.json
 * app/cxn/org.civicrm.myapp/cert/keys.json
 * app/cxn/org.civicrm.myapp/cert/app.crt
 * app/cxn/org.civicrm.myapp/cert/app.req
 * app/cxn/org.civicrm.myapp/cert/democa.crt
 * @endcode
 */
class FileAppStore implements AppStoreInterface {

  /**
   * @var string
   */
  protected $baseDir = NULL;

  protected $appIds = NULL;

  protected $keyPairs = array();

  protected $appMetas = array();

  function __construct($baseDir) {
    $this->baseDir = $baseDir;
  }

  /**
   * @return array
   *   List of App IDs.
   */
  public function getAppIds() {
    if ($this->appIds === NULL) {
      $this->appIds = array();
      if (file_exists($this->baseDir)) {
        $dh = opendir($this->baseDir);
        while (FALSE !== ($item = readdir($dh))) {
          if ($item{0} !== '.' && is_dir($this->baseDir . DIRECTORY_SEPARATOR . $item)) {
            $this->appIds[] = "app:$item";
          }
        }
        closedir($dh);
      }
    }
    return $this->appIds;
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   The application metadata.
   * @see AppMeta
   */
  public function getAppMeta($appId) {
    if (!isset($this->appMetas[$appId])) {
      $metadataFile = $this->getAppDir($appId) . '/metadata.json';
      $certFile = $this->getAppDir($appId) . '/app.crt';

      if (!file_exists($metadataFile)) {
        throw new \RuntimeException("Missing metadata file.");
      }

      $this->appMetas[$appId] = json_decode(file_get_contents($metadataFile), TRUE);

      if (empty($this->appMetas[$appId][$appId])) {
        throw new \RuntimeException("Metadata file does not contain the required appId ($metadataFile)");
      }

      if (!file_exists($certFile)) {
        throw new \RuntimeException("Certificate file missing ($certFile)");
      }
      $this->appMetas[$appId][$appId]['appCert'] = file_get_contents($certFile);
    }
    return $this->appMetas[$appId][$appId];
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPublicKey($appId) {
    $keyPair = $this->getKeyPair($appId);
    return $keyPair['publickey'];
  }

  /**
   * @param string $appId
   *   The application's globally unique ID.
   * @return string
   *   PEM-encoded.
   */
  public function getPrivateKey($appId) {
    $keyPair = $this->getKeyPair($appId);
    return $keyPair['privatekey'];
  }

  /**
   * @return array
   *   Array with elements:
   *     - publickey: string, pem.
   *     - privatekey: string, pem
   */
  public function getKeyPair($appId) {
    if (!$this->keyPairs[$appId]) {
      $keyFile = $this->getAppDir($appId) . '/keys.json';
      if (!file_exists($keyFile)) {
        throw new \RuntimeException("Missing key file.");
      }

      $this->keyPairs[$appId] = KeyPair::load($keyFile);
    }
    return $this->keyPairs[$appId];

  }

  public function getAppDir($appId) {
    return $this->baseDir . DIRECTORY_SEPARATOR . $this->stripAppId($appId);
  }

  /**
   * @param string $appId
   *   Ex: "app:org.example.myapp" or "org.example.myapp".
   * @return string
   *   Ex: "org.example.myapp".
   */
  protected function stripAppId($appId) {
    if (substr($appId, 0, 4) == 'app:') {
      $appId = substr($appId, 4);
      return $appId;
    }
    return $appId;
  }

}
