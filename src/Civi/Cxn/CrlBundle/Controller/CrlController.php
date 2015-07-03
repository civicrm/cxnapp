<?php

namespace Civi\Cxn\CrlBundle\Controller;

use Civi\Cxn\CrlBundle\CrlGenerator;
use Civi\Cxn\Rpc\KeyPair;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

class CrlController extends Controller {

  /**
   * @var string
   *   The base path shared by all CA/CRL data.
   */
  protected $baseDir;

  /**
   * @param ContainerInterface $container
   * @param string $baseDir
   */
  public function __construct(ContainerInterface $container, $baseDir) {
    $this->setContainer($container);
    $this->baseDir = $baseDir;
  }

  /**
   * Get the latest CRL for a given CA.
   *
   * @param string $caName
   *   Ex: 'CiviRootCA'.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function revocationListAction($caName) {
    if ($err = $this->validateCa($caName)) {
      return $err;
    }

    $dirName = $this->baseDir . '/' . $caName;
    $crlGen = new CrlGenerator(
      file_get_contents("$dirName/crldist.crt"),
      KeyPair::load("$dirName/keys.json"),
      file_get_contents("$dirName/ca.crt")
    );
    $crl = $crlGen->generate(
      Yaml::parse(file_get_contents("$dirName/revocations.yml"))
    );

    return new Response($crl, 200, array(
      'Content-type' => 'application/pkix-crl',
    ));
  }

  /**
   * Get the certificate which signed the CRL.
   *
   * @param string $caName
   *   Ex: 'CiviRootCA'.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function certificateAction($caName) {
    if ($err = $this->validateCa($caName)) {
      return $err;
    }

    $file = implode('/', array($this->baseDir, $caName, 'crldist.crt'));
    return new Response(file_get_contents($file), 200, array(
      'Content-type' => 'application/x-pem-file',
    ));
  }

  /**
   * Get the CA for which we manage revocations.
   *
   * @param string $caName
   *   Ex: 'CiviRootCA'.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function caAction($caName) {
    if ($err = $this->validateCa($caName)) {
      return $err;
    }

    $file = implode('/', array($this->baseDir, $caName, 'ca.crt'));
    return new Response(file_get_contents($file), 200, array(
      'Content-type' => 'application/x-pem-file',
    ));
  }

  /**
   * @param string $caName
   *   Ex: 'CiviRootCA'.
   * @return NULL|Response
   *   An error message (Response) if there is a problem.
   *   NULL if OK.
   */
  protected function validateCa($caName) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $caName)) {
      return new Response('Malformed certificate authority name', 404, array(
        'Content-type' => 'text/plain',
      ));
      return FALSE;
    }
    foreach (array('keys.json', 'crldist.crt', 'revocations.yml') as $file) {
      if (!is_readable(implode('/', array($this->baseDir, $caName, $file)))) {
        return new Response("Incomplete certificate authority. Missing: $file", 404, array(
          'Content-type' => 'text/plain',
        ));
      }
    }
    return NULL;
  }

}