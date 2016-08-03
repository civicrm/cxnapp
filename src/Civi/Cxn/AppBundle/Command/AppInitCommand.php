<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\FileAppStore;
use Civi\Cxn\Rpc\AppMeta;
use Civi\Cxn\Rpc\CA;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppInitCommand extends AbstractInitCommand {

  /**
   * @var FileAppStore
   */
  protected $appStore;

  /**
   * @param FileAppStore $appStore
   */
  public function __construct(FileAppStore $appStore) {
    parent::__construct();
    $this->appStore = $appStore;
  }

  protected function configure() {
    $this
      ->setName('cxnapp:init')
      ->setDescription('Initialize the configuration files')
      ->setHelp("Example: cxnapp init \"org.example.myapp\"\n\nIf any files (such as metadata.json or keys.json) already exist, they will be preserved.")
      ->addArgument('appId', InputArgument::REQUIRED, 'The applications guid. (Ex: "app:org.civicrm.myapp")')
      ->addArgument('basedn', InputArgument::OPTIONAL, 'The DN in the application certificate', 'O=DemoApp');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $appId = $input->getArgument('appId');
    if (!preg_match('/^app:/', $appId)) {
      $appId = 'app:' . $appId;
    }

    $appDir = $this->appStore->getAppDir($appId);
    if (!is_dir($appDir)) {
      mkdir($appDir);
    }

    if (!AppMeta::validateAppId($appId)) {
      throw new \Exception("Malformed appId");
    }

    $appDn = $input->getArgument('basedn') . ", CN=$appId";

    $appKeyPair = $this->initKeyFile($output, $appDir . '/keys.json');
    $demoCaCert = $this->initDemoCaCert($output, $input->getArgument('basedn') . ", CN=DemoCA", $appDir . '/democa.crt', $appKeyPair);
    $appCsr = $this->initCsrFile($output, $appDir . '/app.csr', $appKeyPair, $appDn);
    $this->initCertFile($output, $appDir . '/app.crt', $appKeyPair, $demoCaCert, $appCsr);
    $this->initMetadata($output, $appDir . '/metadata.json', $appId);

    print_r($this->appStore->getAppMeta($appId));
  }

  /**
   * @param OutputInterface $output
   * @param string $metadataFile
   * @param string $appId
   * @return array
   *   AppMeta.
   */
  protected function initMetadata(OutputInterface $output, $metadataFile, $appId) {
    if (!file_exists($metadataFile)) {
      $output->writeln("<info>Create metadata file ({$metadataFile})</info>");
      $appMeta = array(
        $appId => array(
          'title' => 'Example App',
          'desc' => 'This is the adhoc connection app. Once connected, the app-provider can make API calls to your site.',
          'appId' => $appId,
          'appCert' => '*PLACEHOLDER*',
          'appUrl' => '*PLACEHOLDER*',
          'links' => array(
            'docs' => FALSE,
            'logs' => FALSE,
            'settings' => FALSE,
            'support' => FALSE,
            'welcome' => FALSE,
          ),
          'perm' => array(
            'desc' => 'Description/rationale for permissions',
            'api' => array(
              array('version' => 3, 'entity' => '*', 'actions' => '*', 'required' => array(), 'fields' => '*'),
            ),
            'grant' => '*',
          ),
        ),
      );
      file_put_contents($metadataFile, json_encode($appMeta, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0));
      return $appMeta;
    }
    else {
      $output->writeln("<info>Load existing metadata file ({$metadataFile})</info>");
      $appMeta = json_decode(file_get_contents($metadataFile), TRUE);
      return $appMeta;
    }
  }

}
