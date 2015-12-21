<?php

namespace Civi\Cxn\AddressCheckerBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressCheckerTest extends WebTestCase {

  public function examples() {
    $examples = array();
    $examples[] = array('http://localhost/sites/all/modules/extern/cxn.php', 'private-ip');
    $examples[] = array('http://127.0.1.1/sites/all/modules/extern/cxn.php', 'private-ip');
    $examples[] = array('http://192.168.55.66/sites/all/modules/extern/cxn.php', 'private-ip');
    $examples[] = array('http://[::1]/sites/all/modules/extern/cxn.php', 'no-ipv6');
    $examples[] = array('http://[::1]:80/sites/all/modules/extern/cxn.php', 'no-ipv6');
    $examples[] = array('http://totally.inv.alid:123/sites/all/modules/extern/cxn.php', 'bad-dns');
    $examples[] = array('http://civicrm.org/trick/ster', 'bad-path');
    $examples[] = array(
      'http://civicrm.org:1234/sites/civicrm.org/modules/extern/cxn.php',
      'bad-socket',
    );
    $examples[] = array('ftp://civicrm.org/sites/civicrm.org/modules/extern/cxn.php', 'bad-scheme');
    $examples[] = array('http://civicrm.org/sites/civicrm.org/modules/extern/cxn.php', 'ok');
    $examples[] = array('https://civicrm.org/mycms/modules/extern/cxn.php', 'ok');

    return $examples;
  }

  /**
   * @param $url
   * @param $expectedResult
   * @dataProvider examples
   */
  public function testViaPhp($url, $expectedResult) {
    $client = static::createClient();
    $checker = $client->getKernel()->getContainer()->get('civi_cxn_address_checker.address_checker');
    $this->assertEquals($expectedResult, $checker->checkUrl($url));
  }

  public function exampleIps() {
    return array(
      array('127.0.0.1', FALSE),
      array('127.254.0.1', FALSE),
      array('192.168.233.25', FALSE),
      array('192.167.233.25', TRUE),
      array('127.169.0.1', FALSE),
      array('172.15.0.1', TRUE),
      array('172.17.23.45', FALSE),
    );
  }

  /**
   * @param string $ip
   * @dataProvider exampleIps
   */
  public function testCheckPublicIp($ip, $expectResult) {
    $client = static::createClient();
    $checker = $client->getKernel()->getContainer()->get('civi_cxn_address_checker.address_checker');
    $this->assertEquals($expectResult, $checker->checkPublicIp($ip));
  }

}
