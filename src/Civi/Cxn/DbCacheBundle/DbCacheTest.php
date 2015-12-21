<?php

namespace Civi\Cxn\DbCacheBundle\Tests;

use Civi\Cxn\DbCacheBundle\DbCache;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DbCachetest extends WebTestCase {

  public function setUp() {
    parent::setUp();
    Time::resetTime();
  }

  public function saveFetchExamples() {
    $examples = array();
    $examples[] = array(123);
    $examples[] = array('foo');
    $examples[] = array(array(456));
    $examples[] = array(array('a' => 'Eh?', 'b' => 'Bee!'));
    return $examples;
  }

  /**
   * @dataProvider saveFetchExamples
   */
  public function testLifecycle($data) {
    $cache = $this->createCache();
    $cache->flushAll();

    $key = __FUNCTION__;

    $this->assertTrue($cache->save($key, $data));

    $cache = $this->createCache();

    $this->assertEquals($data, $cache->fetch($key));
    $this->assertTrue($cache->contains($key));

    $this->assertEquals(NULL, $cache->fetch('i-do-not-exist!'));
    $this->assertFalse($cache->contains('i-do-not-exist!'));

    $this->assertTrue($cache->delete($key));
    $this->assertFalse($cache->contains($key));
    $this->assertEquals(NULL, $cache->fetch($key));
  }

  public function expirationExamples() {
    $examples = array(); // startTime, ttl, array($checkTime=>$expectContains)
    $examples[] = array('10:30:00', 60, array('10:30:30' => TRUE, '10:31:01' => FALSE));
    $examples[] = array('10:30:00', 3600, array('11:29:30' => TRUE, '11:30:01' => FALSE));
    $examples[] = array('10:30:00', NULL, array('11:29:30' => TRUE, '11:30:01' => TRUE));
    return $examples;
  }

  /**
   * @param string $startTime
   * @param int $ttl
   * @param array $expectations
   * @dataProvider expirationExamples
   */
  public function testExpiration($startTime, $ttl, $expectations) {
    $cache = $this->createCache();
    $cache->flushAll();

    $key = __FUNCTION__;
    $data = array(123);

    Time::setTime($startTime);
    $this->assertTrue($cache->save($key, $data, $ttl));
    $this->assertTrue($cache->contains($key));

    foreach ($expectations as $expectTime => $expectContains) {
      Time::setTime($expectTime);
      $this->assertEquals($expectContains, $cache->contains($key), "Check at ($expectTime) for value ($expectContains)");
      if ($expectContains) {
        $this->assertEquals($data, $cache->fetch($key));
      }
      else {
        $this->assertFalse($cache->fetch($key));
      }
    }
  }

  public function testFetchAll() {
    $prefix = __FUNCTION__;

    $cache = $this->createCache();
    $cache->flushAll();
    $this->assertTrue($cache->save("$prefix/a", "Eh?"));
    $this->assertTrue($cache->save("$prefix/b", "Bee!"));
    $this->assertTrue($cache->save("$prefix/c", "See?"));

    $cache = $this->createCache();
    $fetched = $cache->fetchMultiple(array("$prefix/a", "$prefix/c", "$prefix/z"));
    $this->assertEquals("Eh?", $fetched["$prefix/a"]);
    $this->assertEquals("See?", $fetched["$prefix/c"]);
    $this->assertFalse(array_key_exists("$prefix/b", $fetched));
    $this->assertFalse(array_key_exists("$prefix/z", $fetched));
  }

  public function testFlushAll() {
    $prefix = __FUNCTION__;

    $cache = $this->createCache();
    $cache->flushAll();

    $this->assertTrue($cache->save("$prefix/a", "Eh?"));
    $this->assertTrue($cache->save("$prefix/b", "Bee!"));
    $this->assertTrue($cache->save("$prefix/c", "See?"));

    $cache = $this->createCache();

    $this->assertTrue($cache->contains("$prefix/a"));
    $this->assertTrue($cache->contains("$prefix/b"));

    $this->assertTrue($cache->flushAll());
    $this->assertFalse($cache->contains("$prefix/a"));
    $this->assertFalse($cache->contains("$prefix/b"));
  }

  /**
   * @return \Civi\Cxn\DbCacheBundle\DbCache
   */
  protected function createCache() {
    $client = static::createClient();
    /** @var EntityManager $em */
    $em = $client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
    return new DbCache($em);
  }

}
