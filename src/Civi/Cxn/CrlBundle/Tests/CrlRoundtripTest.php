<?php

namespace Civi\Cxn\CrlBundle\Tests\Controller;

use Civi\Cxn\CrlBundle\Command\InitCommand;
use Civi\Cxn\CrlBundle\Command\ValidateCommand;
use Civi\Cxn\Rpc\CA;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CrlControllerTest extends WebTestCase {

  protected $files = array('keys.json', 'crldist.crt', 'crldist.req', 'revocations.yml', 'ca.crt');

  /**
   * Initialize a CRL distribution point (with dummy certs), fetch the CRL, and ensure that
   * it validates.
   */
  public function testInitGetValidate() {
    $this->cleanupCrl('UnitTestCA');
    $this->initCrl('UnitTestCA', 'C=US, CN=Unit Test CA');
    $this->validateCrl('UnitTestCA');
  }

  /**
   * @param string $baseDir
   * @param string $caName
   * @return mixed
   */
  protected function cleanupCrl($caName) {
    $kernel = $this->createKernel();
    $kernel->boot();
    $baseDir = $kernel->getContainer()->getParameter('civi_cxn_crl.path');

    foreach ($this->files as $file) {
      if (file_exists("$baseDir/$caName/$file")) {
        unlink("$baseDir/$caName/$file");
      }
    }
  }

  /**
   * @param string $caName
   * @param string $dn
   */
  protected function initCrl($caName, $dn) {
    $kernel = $this->createKernel();
    $kernel->boot();
    $application = new Application($kernel);
    $application->add(new InitCommand($kernel->getContainer()->getParameter('civi_cxn_crl.path')));
    $command = $application->find('crl:init');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array(
      'command' => $command->getName(),
      'caName' => $caName,
      'dn' => $dn,
    ));

    foreach ($this->files as $file) {
      $this->assertFileExists($kernel->getContainer()->getParameter('civi_cxn_crl.path') . "/UnitTestCA/$file");
    }
  }

  protected function validateCrl($caName) {
    $kernel = $this->createKernel();
    $kernel->boot();
    $application = new Application($kernel);
    $application->add(new ValidateCommand($kernel));
    $command = $application->find('crl:validate');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array(
      'command' => $command->getName(),
      'caName' => $caName,
    ));

    $this->assertEquals("OK\n", $commandTester->getDisplay());
  }

}
