<?php

namespace Civi\Cxn\DirBundle\Tests\Controller;

use Civi\Cxn\Rpc\AppMeta;
use Civi\Cxn\Rpc\Message\AppMetasMessage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DirSvcControllerTest extends WebTestCase {

  /**
   * Ensure that "/" returns a plain-text list of apps.
   */
  public function testIndex() {
    $client = static::createClient();
    $client->request('GET', '/');
    $this->assertRegExp(';text/plain;', $client->getResponse()->headers->get('Content-type'));
    if ($client->getResponse()->getContent()) {
      // TODO Mock the list of apps and check the actual values match.
      $this->assertRegExp(";^==;", $client->getResponse()->getContent());
    }
  }

  /**
   * Ensure that /cxn/apps returns a well-formed list of apps
   * per CiviConnect v0.2.
   */
  public function testApps() {
    $client = static::createClient();
    $client->request('GET', '/cxn/apps');

    $appMetasMessage = AppMetasMessage::decode(NULL, $client->getResponse()->getContent());
    // TODO Specify the certificate to verify when decoding.
    $this->assertNotNull($appMetasMessage);

    $appMetas = $appMetasMessage->getData();
    $this->assertNotEmpty($appMetas, "Ensure that the server returns some list of apps. This may fail if you have not yet initialized any apps.");
    // TODO Mock the list of apps and check the actual values match.

    foreach ($appMetas as $appId => $appMeta) {
      $this->assertTrue(AppMeta::validateAppId($appId));
      $this->assertEmpty(AppMeta::getValidationMessages($appMeta));
    }
  }

}
