<?php

namespace Civi\Cxn\AppBundle\Tests\Command;

use Civi\Cxn\AppBundle\RetryPolicy;

class RetryPolicyTest extends \PHPUnit_Framework_TestCase {

  public function getGoodCases() {
    $cases[] = array(
      '10min (x2); 1day (x4); 7 day (x4)',
      array(
        array(0, 2, 10 * 60),
        array(1, 4, 24 * 60 * 60),
        array(2, 4, 7 * 24 * 60 * 60),
      ),
    );
    $cases[] = array(
      '1min (x2); 10min (x4)',
      array(
        array(0, 2, 60),
        array(1, 4, 10 * 60),
      ),
    );
    $cases[] = array(
      '2week(x2)',
      array(
        array(0, 2, 2 * 7 * 24 * 60 * 60),
      ),
    );
    $cases[] = array(
      '  2  week ( x 2 ); ;',
      array(
        array(0, 2, 2 * 7 * 24 * 60 * 60),
      ),
    );

    return $cases;
  }

  /**
   * @dataProvider getGoodCases
   */
  public function testParse($expr, $expectPolicies) {
    $actualPolicies = RetryPolicy::parse($expr);
    foreach ($actualPolicies as $idx => $actualPolicy) {
      /** @var RetryPolicy $actualPolicy */
      $this->assertEquals($actualPolicy->getLevel(), $expectPolicies[$idx][0]);
      $this->assertEquals($actualPolicy->getCount(), $expectPolicies[$idx][1]);
      $this->assertEquals($actualPolicy->getPeriod(), $expectPolicies[$idx][2]);
    }
    $this->assertEquals(count($actualPolicies), count($expectPolicies));
  }

  public function getBadCases() {
    return array(
      array('week (2x)'),
      array('week (2'),
      array('1min'),
      array('min 2x'),
      array('2xweek'),
      array('2week'),
      array('1min'),
      array('2x min'),
    );
  }

  /**
   * @dataProvider getBadCases
   * @param string $expr
   * @expectedException \InvalidArgumentException
   */
  public function testParseBad($expr) {
    RetryPolicy::parse($expr);
  }

}
