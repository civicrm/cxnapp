<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\Rpc\CA;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractInitCommand extends Command {
  /**
   * @param OutputInterface $output
   * @param $keyFile
   * @return array
   */
  protected function initKeyFile(OutputInterface $output, $keyFile) {
    if (!file_exists($keyFile)) {
      $output->writeln("<info>Create key file ({$keyFile})</info>");
      $appKeyPair = KeyPair::create();
      KeyPair::save($keyFile, $appKeyPair);
      return $appKeyPair;
    }
    else {
      $output->writeln("<info>Load existing key file ({$keyFile})</info>");
      $appKeyPair = KeyPair::load($keyFile);
      return $appKeyPair;
    }
  }

  /**
   * @param OutputInterface $output
   * @param string $demoCaDn
   * @param string $demoCaFile
   * @param array $appKeyPair
   * @return array|string
   */
  protected function initDemoCaCert(OutputInterface $output, $demoCaDn, $demoCaFile, $appKeyPair) {
    if (!file_exists($demoCaFile)) {
      $output->writeln("<info>Create demo CA file ({$demoCaFile})</info>");
      $demoCaCert = CA::create($appKeyPair, $demoCaDn);
      CA::save($demoCaFile, $demoCaCert);
      return $demoCaCert;
    }
    else {
      $output->writeln("<info>Load existing demo CA file ({$demoCaFile})</info>");
      $demoCaCert = CA::load($demoCaFile);
      return $demoCaCert;
    }
  }

  /**
   * @param OutputInterface $output
   * @param $csrFile
   * @param $appKeyPair
   * @param $appDn
   * @return string
   */
  protected function initCsrFile(OutputInterface $output, $csrFile, $appKeyPair, $appDn) {
    if (!file_exists($csrFile)) {
      $output->writeln("<info>Create certificate request ({$csrFile})</info>");
      $appCsr = CA::createAppCSR($appKeyPair, $appDn);
      file_put_contents($csrFile, $appCsr);
      return $appCsr;
    }
    else {
      $output->writeln("<info>Load existing certificate request ({$csrFile})</info>");
      $appCsr = file_get_contents($csrFile);
      return $appCsr;
    }
  }

  /**
   * @param OutputInterface $output
   * @param $csrFile
   * @param $appKeyPair
   * @param $appDn
   * @return string
   */
  protected function initCrlDistCsrFile(OutputInterface $output, $csrFile, $appKeyPair, $appDn) {
    if (!file_exists($csrFile)) {
      $output->writeln("<info>Create certificate request ({$csrFile})</info>");
      $appCsr = CA::createCrlDistCSR($appKeyPair, $appDn);
      file_put_contents($csrFile, $appCsr);
      return $appCsr;
    }
    else {
      $output->writeln("<info>Load existing certificate request ({$csrFile})</info>");
      $appCsr = file_get_contents($csrFile);
      return $appCsr;
    }
  }

  /**
   * @param OutputInterface $output
   * @param $certFile
   * @param $appKeyPair
   * @param $demoCaCert
   * @param $appCsr
   */
  protected function initCertFile(OutputInterface $output, $certFile, $appKeyPair, $demoCaCert, $appCsr) {
    if (!file_exists($certFile)) {
      $output->writeln("<info>Create certificate self-signed ({$certFile})</info>");
      $appCert = CA::signCSR($appKeyPair, $demoCaCert, $appCsr);
      file_put_contents($certFile, $appCert);
    }
    else {
      $output->writeln("<info>Load existing certificate ({$certFile})</info>");
      $appCert = file_get_contents($certFile);
    }
  }

}
