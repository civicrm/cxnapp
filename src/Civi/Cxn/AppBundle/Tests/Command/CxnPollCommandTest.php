<?php

namespace Civi\Cxn\AppBundle\Tests\Command;

use Civi\Cxn\AppBundle\Command\CxnPollCommand;
use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\AppBundle\Event\PollEvent;
use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\AppStore\SingletonAppStore;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CxnPollCommandTest
 * @package Civi\Cxn\AppBundle\Tests\Command
 *
 * The CxnPollCommand provides a CLI interface for periodically running jobs
 * that access remote Civi sites. It's expected that remote sites will fail
 * for any number of reasons (misconfiguration, reconfiguration, shutdown, migration,
 * MitM, etc). The command allows one to gradually de-escalate engagement with
 * sites with persistent problems.
 *
 * To test that the "cxn:poll" command de-escalates correctly, we create a handful of
 * apps/sites/cxns and simulate a call to "cxn:poll" every minute.
 * This creates a list of actualRuns (where a specific app polled a specific site through
 * a specific connection and received a success or failure notification), and we compare
 * that list to expectedRuns.
 *
 * The above is repeated with different CLI options and different sequences of success/failure.
 */
class CxnPollCommandTest extends WebTestCase {

  protected static $DIRTY = 0;

  private $startDate = '2015-01-01';

  /**
   * @var array
   */
  private $pollLogs;

  /**
   * @var array
   */
  private $expectRuns;

  /**
   * Pre-test setup. Insert some connections.
   */
  public function setUp() {
    $container = static::createClient()->getKernel()->getContainer();
    /** @var EntityManager $em */
    $em = $container->get('doctrine.orm.entity_manager');
    $count = $em->createQuery('SELECT count(cx.cxnId) FROM Civi\Cxn\AppBundle\Entity\CxnEntity cx')
      ->getSingleScalarResult();
    if ($count != 0) {
      self::$DIRTY = 1;
      $this->markTestSkipped('Cannot run tests if there are active connections.');
      return;
    }

    // Prepopulate list of sites/cxns for each dummy app.
    $cxns = array(
      // array($cxnId, $appId, $batchCode)
      array('cxn:1-0000', 'app:org.civicrm.polltest.1', 0),
      array('cxn:1-4000', 'app:org.civicrm.polltest.1', 4000),
      array('cxn:1-8000', 'app:org.civicrm.polltest.1', 8000),
      array('cxn:2-0000', 'app:org.civicrm.polltest.2', 0),
      array('cxn:2-4000', 'app:org.civicrm.polltest.2', 4000),
      array('cxn:2-8000', 'app:org.civicrm.polltest.2', 8000),
    );
    foreach ($cxns as $cxn) {
      $cxnEntity = new CxnEntity();
      $cxnEntity
        ->setAppId($cxn[1])
        ->setAppUrl('http://example.com/app')
        ->setBatchCode($cxn[2])
        ->setCxnId($cxn[0])
        ->setPerm(array())
        ->setSecret('asdf')
        ->setSiteUrl('http://example.com/site');
      $em->persist($cxnEntity);
    }
    $em->flush();
  }

  /**
   * Post-test teardown. Remove the test connections.
   */
  public function tearDown() {
    if (self::$DIRTY) {
      return;
    }

    Time::resetTime();

    $client = static::createClient();
    $kernel = $client->getKernel();
    $container = $kernel->getContainer();
    /** @var EntityManager $em */
    $em = $container->get('doctrine.orm.entity_manager');
    $em->createQuery('DELETE FROM \\Civi\\Cxn\\AppBundle\\Entity\\CxnEntity')->execute();
  }

  /**
   * @return array
   *   Each item in the array is a test-case with parts:
   *   0: array $commandArguments: Data to pass via CLI
   *   1: string $startTime: When the first poll runs
   *   2: string $endTime: When the last poll runs
   *   3: array $expectRuns: Tells the story of when the poll-jobs are actually
   *      expected to run. For each expected run, specify the expected timestamp,
   *      the name of the target site/cxn, and the mocked outcome.
   *      The test will fail if actual sequence of runs is shorter, longer,
   *      or with mismatched timestamps.
   */
  public function getCases() {
    $commands['--batch=0/3 --retry="1min (x2); 10min (x4)"'] = array(
      'command' => 'cxn:poll',
      'appId' => 'org.civicrm.polltest.1',
      '--retry' => '1min (x2); 10min (x4)',
      '--name' => 'alt',
      '--batch' => '0/3',
    );
    $commands['--batch=0/3 --retry="1min (x1); 10min (x5)"'] = array(
      'command' => 'cxn:poll',
      'appId' => 'org.civicrm.polltest.1',
      '--retry' => '1min (x1); 10min (x5)',
      '--name' => 'alt',
      '--batch' => '0/3',
    );

    $cases[] = array(
      $commands['--batch=0/3 --retry="1min (x2); 10min (x4)"'],
      '00:00',
      '01:00',
      array(
        // array($expectTime, $expectCxnId, $resultCode, $expectLevel, $expectCount)
        array('00:00', 'cxn:1-0000', 'ok', 0, 0),
        array('00:01', 'cxn:1-0000', 'ok', 0, 0), // Still OK
        array('00:02', 'cxn:1-0000', 'error', 0, 1), // First problem; stick to 1min interval
        array('00:03', 'cxn:1-0000', 'error', 1, 0), // Second problem; throttle to 10min interval
        array('00:13', 'cxn:1-0000', 'error', 1, 1), // Third problem; stick to 10min interval
        array('00:23', 'cxn:1-0000', 'error', 1, 2), // Fourth problem
        array('00:33', 'cxn:1-0000', 'error', 1, 3), // Fourth problem
        array('00:43', 'cxn:1-0000', 'error', 2, 0), // Fifth problem; give up
      ),
    );

    $cases[] = array(
      $commands['--batch=0/3 --retry="1min (x2); 10min (x4)"'],
      '00:00',
      '00:04',
      array(
        // array($expectTime, $expectCxnId, $resultCode, $expectLevel, $expectCount)
        array('00:00', 'cxn:1-0000', 'ok', 0, 0),
        array('00:01', 'cxn:1-0000', 'ok', 0, 0),
        array('00:02', 'cxn:1-0000', 'ok', 0, 0),
        array('00:03', 'cxn:1-0000', 'ok', 0, 0),
        array('00:04', 'cxn:1-0000', 'ok', 0, 0),
      ),
    );

    $cases[] = array(
      $commands['--batch=0/3 --retry="1min (x2); 10min (x4)"'],
      '00:00',
      '00:13',
      array(
        // array($expectTime, $expectCxnId, $resultCode, $expectLevel, $expectCount)
        array('00:00', 'cxn:1-0000', 'error', 0, 1),
        array('00:01', 'cxn:1-0000', 'error', 1, 0),
        array('00:11', 'cxn:1-0000', 'ok', 0, 0),
        array('00:12', 'cxn:1-0000', 'ok', 0, 0),
        array('00:13', 'cxn:1-0000', 'ok', 0, 0),
      ),
    );

    $cases[] = array(
      $commands['--batch=0/3 --retry="1min (x1); 10min (x5)"'],
      '00:00',
      '00:30',
      array(
        // array($expectTime, $expectCxnId, $resultCode, $expectLevel, $expectCount)
        array('00:00', 'cxn:1-0000', 'error', 1, 0),
        array('00:10', 'cxn:1-0000', 'error', 1, 1),
        array('00:20', 'cxn:1-0000', 'error', 1, 2),
        array('00:30', 'cxn:1-0000', 'ok', 0, 0),
      ),
    );

    return $cases;
  }

  /**
   * @param array $commandOptions
   * @param string $startAt
   * @param string $endAt
   * @param array $expectRuns
   * @dataProvider getCases
   */
  public function testPoll($commandOptions, $startAt, $endAt, $expectRuns) {
    $endAtTs = strtotime("{$this->startDate} $endAt");

    $actualRuns = 0;
    $invocationCount = 0;
    $this->expectRuns = $expectRuns;
    do {
      $this->pollLogs = array();

      Time::setTime("{$this->startDate} $startAt +{$invocationCount} min");
      $application = $this->createApplication();
      $command = $application->find('cxn:poll');
      $commandTester = new CommandTester($command);
      $commandTester->execute($commandOptions);

      foreach ($this->pollLogs as $pollLog) {
        $actualRuns++;
        $actualTime = date('H:i', $pollLog['time']);
        list ($expectTime, $expectCxnId, $resultCode, $expectLevel, $expectCount) = $pollLog['expectRun'];
        $this->assertEquals($expectTime, $actualTime, 'Unexpected execution timestamp');
        $this->assertEquals($expectCxnId, $pollLog['cxnId'], 'Unexpected cxnId');
        $this->assertQueryScalarResult($expectLevel, 'SELECT p.pollLevel FROM Civi\Cxn\AppBundle\Entity\PollStatus p JOIN p.cxn cxn WHERE p.jobName = :jobName AND cxn.cxnId = :cxnId', array(
          'jobName' => 'alt',
          'cxnId' => $expectCxnId,
        ));
        $this->assertQueryScalarResult($expectCount, 'SELECT p.pollCount FROM Civi\Cxn\AppBundle\Entity\PollStatus p JOIN p.cxn cxn WHERE p.jobName = :jobName AND cxn.cxnId = :cxnId', array(
          'jobName' => 'alt',
          'cxnId' => $expectCxnId,
        ));
      }

      // Advance one minute at a time
      $invocationCount++;
    } while (Time::getTime() < $endAtTs);

    $this->assertEquals(count($expectRuns), $actualRuns);
  }

  public function onPoll(PollEvent $event) {
    if ($event->getAppId() !== 'app:org.civicrm.polltest.1' || $event->getJobName() !== 'alt') {
      throw new \RuntimeException('In tests configuration, only org.civicrm.polltest.1#alt should fire.');
    }

    if (empty($this->expectRuns)) {
      throw new \RuntimeException('Fired poll event, but no more events were expected');
    }

    $appMeta = $event->getAppMeta();
    $this->assertEquals('http://example.com', $appMeta['appUrl']);
    $this->assertTrue($event->getApiClient() instanceof ApiClient);

    $expectRun = array_shift($this->expectRuns);

    list (, , $resultCode, ,) = $expectRun;
    $event->setError($resultCode === 'error');

    $this->pollLogs[] = array(
      'time' => Time::getTime(),
      'expectRun' => $expectRun,
      'cxnId' => $event->getCxnEntity()->getCxnId(),
    );
  }

  /**
   * @return \Symfony\Bundle\FrameworkBundle\Console\Application
   */
  protected function createApplication() {
    $kernel = static::createClient()->getKernel();
    $container = $kernel->getContainer();

    $container->get('event_dispatcher')
      ->addListener('app:org.civicrm.polltest.1:job=default:poll', array($this, 'onPoll'));
    $container->get('event_dispatcher')
      ->addListener('app:org.civicrm.polltest.1:job=alt:poll', array($this, 'onPoll'));
    $container->get('event_dispatcher')
      ->addListener('app:org.civicrm.polltest.2:job=default:poll', array($this, 'onPoll'));

    $appMeta = array(
      'appId' => 'app:org.civicrm.polltest.1',
      'title' => 'Poll Test 1',
      'appCert' => 'asdf',
      'appUrl' => 'http://example.com',
      'perm' => array(
        'api' => array(),
        'grant' => array(),
      ),
    );

    $application = new Application($kernel);
    $application->add(new CxnPollCommand(
      $container->get('doctrine.orm.entity_manager'),
      $container->get('event_dispatcher'),
      $container->get('logger'),
      $container->getParameter('kernel.root_dir') . '/lock',
      // $container->get('civi_cxn_app.app_store'),
      new SingletonAppStore($appMeta['appId'], $appMeta, NULL, NULL),
      $container->get('civi_cxn_app.cxn_store')
    ));
    return $application;
  }

  protected function assertQueryScalarResult($expectValue, $query, $parameters) {
    $container = static::createClient()->getKernel()->getContainer();
    /** @var EntityManager $em */
    $em = $container->get('doctrine.orm.entity_manager');
    $query = $em->createQuery($query);
    $query->setParameters($parameters);
    $this->assertEquals($expectValue, $query->getSingleScalarResult());
  }

}
