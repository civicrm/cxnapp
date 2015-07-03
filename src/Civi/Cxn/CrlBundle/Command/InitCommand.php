<?php
namespace Civi\Cxn\CrlBundle\Command;

use Civi\Cxn\AppBundle\Command\AbstractInitCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends AbstractInitCommand {

  /**
   * @var string
   */
  protected $baseDir;

  public function __construct($baseDir) {
    parent::__construct();
    $this->baseDir = $baseDir;
  }

  protected function configure() {
    $this
      ->setName('crl:init')
      ->setDescription('Initialize the configuration files')
      ->setHelp('Example: crl:init CiviTestRootCA "C=US, O=My Org, CN=My Test Root"')
      ->addArgument('caName', InputArgument::REQUIRED, 'Short symbolic name of the certificate authority')
      ->addArgument('dn', InputArgument::REQUIRED, 'The full DN of the certificate authority');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $caName = $input->getArgument('caName');
    $appDn = $input->getArgument('dn');

    if (!is_dir($this->baseDir)) {
      mkdir($this->baseDir);
    }
    if (!is_dir($this->baseDir . "/$caName")) {
      mkdir($this->baseDir . "/$caName");
    }

    $appKeyPair = $this->initKeyFile($output, $this->baseDir . "/$caName/keys.json");
    $demoCaCert = $this->initDemoCaCert($output, $appDn, $this->baseDir . "/$caName/ca.crt", $appKeyPair);
    $appCsr = $this->initCrlDistCsrFile($output, $this->baseDir . "/$caName/crldist.csr", $appKeyPair, $appDn);
    $this->initCertFile($output, $this->baseDir . "/$caName/crldist.crt", $appKeyPair, $demoCaCert, $appCsr);
    $this->initRevocations($output, $this->baseDir . "/$caName/revocations.yml");
  }

  /**
   * @param OutputInterface $output
   * @param string $revFile
   */
  protected function initRevocations(OutputInterface $output, $revFile) {
    if (!file_exists($revFile)) {
      $output->writeln("<info>Create revocations file ({$revFile})</info>");
      $defaults = array(
        'serialNumberNonce' => rand(1, 100000),
        'ttl' => '+7 days',
        'certs' => array(),
      );
      file_put_contents($revFile, Yaml::dump($defaults));
    }
    else {
      $output->writeln("<info>Load existing revocations file ({$revFile})</info>");
    }
  }

}
