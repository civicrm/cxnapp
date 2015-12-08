<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\BatchHelper;
use Civi\Cxn\AppBundle\PidLock;
use Civi\Cxn\AppBundle\PollRunner;
use Civi\Cxn\AppBundle\RetryPolicy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CxnPollCommand extends Command {

  const DEFAULT_VERSION = 3;
  const WAIT = 2;

  /**
   * @var LoggerInterface
   */
  protected $log;

  /**
   * @var string
   *   A folder in which to write PID locks.
   */
  protected $lockDir;

  /**
   * @var \Civi\Cxn\AppBundle\PollRunner
   */
  protected $pollRunner;

  public function __construct(LoggerInterface $log = NULL, $lockDir, PollRunner $pollRunner) {
    parent::__construct();
    $this->log = $log;
    $this->lockDir = $lockDir;
    $this->pollRunner = $pollRunner;
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
    $batchId = $input->getOption('batch');

    if (!is_dir($this->lockDir)) {
      if (!mkdir($this->lockDir)) {
        throw new \RuntimeException("Failed to find or create lock dir ({$this->lockDir})");
      }
    }

    $lockId = md5(implode(':', array($appId, $jobName, $batchId)));
    $lock = new PidLock(NULL, $this->lockDir . '/' . $lockId);
    if (!$lock->acquire(self::WAIT)) {
      $output->writeln("<info>Another thread is processing this batch (appId={$appId}, job={$jobName}, batch={$batchId}).</info>");
      return;
    }

    $batchRange = BatchHelper::getBatchRange(BatchHelper::parseBatchId($input->getOption('batch')));
    $this->pollRunner->runApp($appId, $jobName, $batchRange, $retryPolicies);
  }

}
