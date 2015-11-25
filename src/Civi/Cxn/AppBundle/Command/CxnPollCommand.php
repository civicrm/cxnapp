<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\BatchHelper;
use Civi\Cxn\AppBundle\Entity\PollStatus;
use Civi\Cxn\AppBundle\Event\PollEvent;
use Civi\Cxn\AppBundle\Event\PostPollEvent;
use Civi\Cxn\AppBundle\Event\PrePollEvent;
use Civi\Cxn\AppBundle\PidLock;
use Civi\Cxn\AppBundle\RetryPolicy;
use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CxnPollCommand extends Command {

  const DEFAULT_VERSION = 3;
  const WAIT = 2;

  /**
   * @var LoggerInterface
   */
  protected $log;

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var string
   *   A folder in which to write PID locks.
   */
  protected $lockDir;

  /**
   * @var \Civi\Cxn\Rpc\AppStore\AppStoreInterface
   */
  protected $appStore;

  /**
   * @var \Civi\Cxn\Rpc\CxnStore\CxnStoreInterface
   */
  protected $cxnStore;

  public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher, LoggerInterface $log = NULL, $lockDir, AppStoreInterface $appStore, CxnStoreInterface $cxnStore) {
    parent::__construct();
    $this->log = $log;
    $this->dispatcher = $dispatcher;
    $this->em = $em;
    $this->lockDir = $lockDir;
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
  }

  protected function configure() {
    $this
      ->setName('cxn:poll')
      ->setDescription('Fire recurring jobs for all connections.')
      ->addArgument('appId', InputArgument::REQUIRED, 'Application ID')
      ->addOption('batch', NULL, InputOption::VALUE_REQUIRED, 'Batch expression ({batchId}/{batchCount})', '0/1')
      ->addOption('retry', NULL, InputOption::VALUE_REQUIRED, 'Retry expression ({retryLimit}x{retryPeriod}; {retryLimit}x{retryPeriod}; ...)', "1 hour (x48); 1 day (x90)")
      ->addOption('name', NULL, InputOption::VALUE_REQUIRED, 'Job name', 'default')
      ->setHelp("
Fire recurring jobs for all connections.

Example:
  cxn:poll org.civicrm.myapp --batch=0/3 --retry='1 hour (x24); 1 day (x30)'

For more discussion of polling, see cxnapp/doc/polling.md.
");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $appId = $input->getArgument('appId');
    if (!preg_match('/^app:/', $appId)) {
      $appId = 'app:' . $appId;
    }
    $retryPolicies = RetryPolicy::parse($input->getOption('retry'));
    $jobName = $input->getOption('name');
    $batchRange = BatchHelper::getBatchRange(BatchHelper::parseBatchId($input->getOption('batch')));

    if (!is_dir($this->lockDir)) {
      if (!mkdir($this->lockDir)) {
        throw new \RuntimeException("Failed to find or create lock dir ({$this->lockDir})");
      }
    }

    $batchProcessId = md5(implode(':', array($appId, $jobName, $batchRange[0], $batchRange[1])));
    $lock = new PidLock(NULL, $this->lockDir . '/' . $batchProcessId);
    if (!$lock->acquire(self::WAIT)) {
      $output->writeln("<info>Another thread is processing this batch (appId={$appId}, job={$jobName}, batchRange={$batchRange[0]}-{$batchRange[1]}).</info>");
      return;
    }

    $this->initNew($appId, $jobName, $batchRange);
    $this->poll($appId, $jobName, $batchRange, $retryPolicies);
  }

  /**
   * Initialize tracking records for any new, unvisited connections.
   *
   * @param string $appId
   *   Ex: 'app:org.civicrm.cron'.
   * @param string $jobName
   * @param array $batchRange
   *   Ex: array(2500, 4999).
   */
  protected function initNew($appId, $jobName, $batchRange) {
    $newCxns = $this->findNew($appId, $jobName, $batchRange);
    foreach ($newCxns as $newCxn) {
      $pollStatus = new PollStatus();
      $pollStatus
        ->setCxn($newCxn)
        ->setJobName($jobName)
        ->setPollLevel(0)
        ->setPollCount(0)
        ->setLastRun(0);
      $this->em->persist($pollStatus);
    }
    if (!empty($newCxns)) {
      $this->em->flush();
    }
  }

  /**
   * Visit each of the pending connections.
   *
   * @param string $appId
   * @param string $jobName
   * @param array $batchRange
   * @param array $retryPolicies
   *   Array(RetryPolicy).
   */
  protected function poll($appId, $jobName, $batchRange, $retryPolicies) {
    $this->log->info("Poll [{appId} {jobName}]", array(
      'appId' => $appId,
      'jobName' => $jobName,
    ));

    $appMeta = $this->appStore->getAppMeta($appId);

    $prePollEvent = new PrePollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array("{$appId}:job={$jobName}:pre-poll", "civi_cxn.pre-poll"), $prePollEvent);

    foreach ($retryPolicies as $level => $retryPolicy) {
      /** @var RetryPolicy $retryPolicy */
      $results = $this->findPending($appId, $jobName, $retryPolicy, $batchRange);
      foreach ($results as $pollStatus) {
        /** @var \Civi\Cxn\AppBundle\Entity\PollStatus $pollStatus */

        $this->log->info("Poll [{appId} {jobName}] => [{cxnId}]", array(
          'appId' => $appId,
          'jobName' => $jobName,
          'cxnId' => $pollStatus->getCxn()->getCxnId(),
        ));

        $apiClient = new ApiClient($appMeta, $this->cxnStore, $pollStatus->getCxn()->getCxnId());
        $apiClient->setLog($this->log);

        $pollEvent = new PollEvent($appId, $jobName, $pollStatus->getCxn(), $apiClient, $appMeta);
        $this->dispatchEach(array("{$appId}:job={$jobName}:poll", "civi_cxn.poll"), $pollEvent);

        $success = !$pollEvent->isError();
        list ($nextLevel, $nextCount) = RetryPolicy::next($retryPolicies, $pollStatus->getPollLevel(), $pollStatus->getPollCount(), $success);
        $pollStatus->setPollLevel($nextLevel);
        $pollStatus->setPollCount($nextCount);
        $pollStatus->setLastRun(Time::getTime());

        $this->em->flush($pollStatus);
      }
    }

    $postPollEvent = new PostPollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array("{$appId}:job={$jobName}:post-poll", "civi_cxn.post-poll"), $postPollEvent);
  }

  /**
   * Dispatch an event which has multiple names/aliases.
   *
   * @param array $names
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  private function dispatchEach($names, \Symfony\Component\EventDispatcher\Event $event) {
    foreach ($names as $name) {
      if ($event->isPropagationStopped()) {
        break;
      }
      $this->dispatcher->dispatch($name, $event);
    }
  }

  /**
   * Get a list of new connections which have not been polled yet.
   *
   * @param $appId
   * @param $jobName
   * @param $batchRange
   * @return array
   *   Array<CxnEntity>
   */
  protected function findNew($appId, $jobName, $batchRange) {
    $newCxns = $this->em
      ->createQuery('
        SELECT cxn
        FROM Civi\Cxn\AppBundle\Entity\CxnEntity cxn
        LEFT JOIN cxn.pollStatuses ps WITH ps.cxn = cxn AND ps.jobName = :jobName
        WHERE cxn.appId = :appId
        AND cxn.batchCode >= :batchFirst
        AND cxn.batchCode <= :batchLast
        AND ps IS NULL
      ')
      ->setParameters(array(
        'appId' => $appId,
        'jobName' => $jobName,
        'batchFirst' => $batchRange[0],
        'batchLast' => $batchRange[1],
      ))
      ->getResult();
    return $newCxns;
  }


  /**
   * Get a list of connections which are ripe for polling again.
   *
   * @param string $appId
   * @param string $jobName
   * @param RetryPolicy $retryPolicy
   * @param array $batchRange
   * @return array
   *   Array<PollStatus>.
   */
  protected function findPending($appId, $jobName, $retryPolicy, $batchRange) {
    $lastRunThreshold = Time::getTime() - $retryPolicy->getPeriod();
    $query = $this->em
      ->createQuery('
          SELECT ps
          FROM Civi\Cxn\AppBundle\Entity\PollStatus ps
          INNER JOIN ps.cxn cxn WITH ps.cxn = cxn
          WHERE ps.jobName = :jobName
          AND cxn.appId = :appId
          AND cxn.batchCode >= :batchFirst
          AND cxn.batchCode <= :batchLast
          AND ps.pollLevel = :pollLevel
          AND ps.lastRun < = :lastRunThreshold
        ')
      ->setParameters(array(
        'appId' => $appId,
        'jobName' => $jobName,
        'batchFirst' => $batchRange[0],
        'batchLast' => $batchRange[1],
        'pollLevel' => $retryPolicy->getLevel(),
        'lastRunThreshold' => $lastRunThreshold,
      ));
    $results = $query->getResult();
    return $results;
  }


}
