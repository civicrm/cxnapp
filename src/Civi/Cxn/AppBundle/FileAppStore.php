<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

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
 * app/cxn/org.civicrm.myapp/cert/app.csr
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

  protected $router;

  public function __construct($baseDir, Router $router) {
    $this->baseDir = $baseDir;
    $this->router = $router;
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
          if ($item{0} == '.') {
            continue;
          }
          $appDir = $this->getAppDir($item);
          if (is_dir($appDir) && file_exists("$appDir/metadata.json") && file_exists("$appDir/app.crt") && !file_exists("$appDir/disable")) {
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
      $disableFile = $this->getAppDir($appId) . '/disable';

      if (file_exists($disableFile)) {
        throw new \RuntimeException("Application is disabled ($disableFile).");
      }

      if (!file_exists($metadataFile)) {
        throw new \RuntimeException("Missing metadata file ($metadataFile).");
      }

      $this->appMetas[$appId] = json_decode(file_get_contents($metadataFile), TRUE);

      if (empty($this->appMetas[$appId][$appId])) {
        throw new \RuntimeException("Metadata file does not contain the required appId ($metadataFile)");
      }

      if (!file_exists($certFile)) {
        throw new \RuntimeException("Missing certificate file ($certFile)");
      }
      $this->appMetas[$appId][$appId]['appCert'] = file_get_contents($certFile);

      $this->appMetas[$appId][$appId]['appUrl'] = $this->router->generate(
        'civi_cxn_app_register',
        array('appId' => $appId),
        UrlGeneratorInterface::ABSOLUTE_URL
      );
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
   * @param string $appId
   *   The application's globally unique ID.
   * @return array
   *   Array with elements:
   *     - publickey: string, pem.
   *     - privatekey: string, pem
   */
  public function getKeyPair($appId) {
    if (!isset($this->keyPairs[$appId])) {
      $keyFile = $this->getAppDir($appId) . '/keys.json';
      if (!file_exists($keyFile)) {
        throw new \RuntimeException("Missing key file ($keyFile).");
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
