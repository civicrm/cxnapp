<?php

namespace Civi\Cxn\AddressCheckerBundle\Tests\Controller;

use Doctrine\Common\Cache\ClearableCache;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressCheckerControllerTest extends WebTestCase {

  public function examples() {
    // For more thorough examples, see AddressCheckerTest.
    $examples = array();
    $examples[] = array('http://localhost/sites/all/modules/extern/cxn.php', FALSE);
    $examples[] = array('http://civicrm.org/sites/civicrm.org/modules/extern/cxn.php', TRUE);
    return $examples;
  }

  /**
   * @param $url
   * @param $expectedResult
   * @dataProvider examples
   */
  public function testViaWeb($url, $expectedResult) {
    $client = static::createClient();
    /** @var ClearableCache $cache */
    $cache = $client->getKernel()->getContainer()->get('civi_cxn_address_checker.cache');
    $cache->deleteAll();

    $client->request('POST', '/check-addr', array(
      'url' => $url,
    ));

    $msg = sprintf("Error in response url=[%s] code=[%s] content=[%s]",
      $client->getRequest()->getRequestUri(),
      $client->getResponse()->getStatusCode(),
      $client->getResponse()->getContent()
    );

    $this->assertEquals(200, $client->getResponse()->getStatusCode(), $msg);
    $data = json_decode($client->getResponse()->getContent(), TRUE);
    $this->assertTrue(is_array($data), $msg);
    $this->assertEquals($expectedResult, $data['result'], $msg);
  }

}
