<?php
namespace Civi\Cxn\CrlBundle\Command;

use Civi\Cxn\Rpc\CA;
use Civi\Cxn\Rpc\DefaultCertificateValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class ValidateCommand extends Command {

  /**
   * @var Kernel
   */
  protected $kernel;

  protected $client;

  public function __construct(Kernel $kernel) {
    parent::__construct();
    $this->kernel = $kernel;
  }

  protected function configure() {
    $this
      ->setName('crl:validate')
      ->setDescription('Check that the certs and CRLs are consistent')
      ->addArgument('caName', InputArgument::REQUIRED, 'Short symbolic name of the certificate authority');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $caName = $input->getArgument('caName');
    if (empty($caName)) {
      throw new \RuntimeException("Missing caName");
    }

    // Note: The following routes match the records in routing.yml.
    // Update them in tandem.

    $caCertPem = $this->doGet(array(
      '_controller' => 'civi_cxn_crl.crl_controller:caAction',
      'caName' => $caName,
    ));

    $distCertPem = $this->doGet(array(
      '_controller' => 'civi_cxn_crl.crl_controller:certificateAction',
      'caName' => $caName,
    ));

    $crlRaw = $this->doGet(array(
      '_controller' => 'civi_cxn_crl.crl_controller:revocationListAction',
      'caName' => $caName,
    ));

    $validator = new DefaultCertificateValidator($caCertPem, $distCertPem, $crlRaw, NULL);
    $validator->validateCert($distCertPem);

    $output->writeln("OK");
  }

  /**
   * @param OutputInterface $output
   * @param string $revFile
   */
  protected function initRevocations(OutputInterface $output, $revFile) {
    if (!file_exists($revFile)) {
      $output->writeln("<info>Create apps file ({$revFile})</info>");
      $defaults = array(
        'serialNumber' => 1,
        'certs' => array(),
      );
      file_put_contents($revFile, Yaml::dump($defaults));
    }
    else {
      $output->writeln("<info>Load existing apps file ({$revFile})</info>");
    }
  }

  /**
   * @param array $attributes
   *   Request attributes.
   * @return string
   *   Content of the response.
   */
  protected function doGet($attributes) {
    $request = new Request(array(), array(), $attributes);
    $response = $this->kernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    $content = $response->getContent();
    if ($response->getStatusCode() != 200 || empty($content)) {
      throw new \RuntimeException(sprintf(
        "Invalid response for %s: %s",
        $attributes['_controller'],
        $content
      ));
    }
    return $content;
  }

}
