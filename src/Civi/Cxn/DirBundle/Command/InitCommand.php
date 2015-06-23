<?php
namespace Civi\Cxn\DirBundle\Command;

use Civi\Cxn\Rpc\CA;
use Civi\Cxn\Rpc\Constants;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command {

  /**
   * @var string
   */
  protected $keyFile, $csrFile, $crtFile, $appsFile;

  function __construct($keyFile, $csrFile, $crtFile, $appsFile) {
    parent::__construct();
    $this->keyFile = $keyFile;
    $this->csrFile = $csrFile;
    $this->crtFile = $crtFile;
    $this->appsFile = $appsFile;
  }


  protected function configure() {
    $this
      ->setName('dirsvc:init')
      ->setDescription('Initialize the configuration files')
      ->setHelp('Example: cxndir init "C=US, O=My Org')
      ->addArgument('basedn', InputArgument::OPTIONAL, 'The base DN for the CSR+certificate', 'C=US, O=DemoDir');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    foreach (array($this->keyFile, $this->csrFile, $this->crtFile, $this->appsFile) as $file) {
      $dir = dirname($file);
      if (!is_dir($dir)) {
        mkdir($dir);
      }
    }

    $appDn = $input->getArgument('basedn') . ', CN=' . Constants::OFFICIAL_APPMETAS_CN;

    if (!file_exists($this->keyFile)) {
      $output->writeln("<info>Create key file ({$this->keyFile})</info>");
      $appKeyPair = KeyPair::create();
      KeyPair::save($this->keyFile, $appKeyPair);
    }
    else {
      $output->writeln("<info>Load existing key file ({$this->keyFile})</info>");
      $appKeyPair = KeyPair::load($this->keyFile);
    }

    if (!file_exists($this->csrFile)) {
      $output->writeln("<info>Create certificate request ({$this->csrFile})</info>");
      $appCsr = CA::createCSR($appKeyPair, $appDn);
      file_put_contents($this->csrFile, $appCsr);
    }
    else {
      $output->writeln("<info>Load existing certificate request ({$this->csrFile})</info>");
      $appCsr = file_get_contents($this->csrFile);
    }

    if (!file_exists($this->crtFile)) {
      $output->writeln("<info>Create certificate ({$this->crtFile})</info>");
      $appCert = CA::createSelfSignedCert($appKeyPair, $appDn);
      file_put_contents($this->crtFile, $appCert);
    }
    else {
      $output->writeln("<info>Load existing certificate ({$this->crtFile})</info>");
      $appCert = file_get_contents($this->crtFile);
    }

    if (!file_exists($this->appsFile)) {
      $output->writeln("<info>Create apps file ({$this->appsFile})</info>");
      file_put_contents($this->appsFile, json_encode(array(), defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0));
    }
    else {
      $output->writeln("<info>Load existing apps file ({$this->appsFile})</info>");
    }
  }

}
