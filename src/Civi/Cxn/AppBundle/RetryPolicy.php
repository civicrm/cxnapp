<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Civi\Cxn\Rpc\Exception\GarbledMessageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RetryPolicy {

  /**
   * @param string $exprs
   *   ex: "2x 10 min; 4x 1 hour; 8x 2 hour";
   * @return array
   */
  public static function parse($exprs) {
    $policies = array();
    $level = 0;
    $exprs = trim(strtolower($exprs), "; \r\n");
    foreach (explode(';', $exprs) as $expr) {
      if (preg_match('/^\s*([0-9]+)\s*(hour|min|sec|day|week|month|year)\s*\(\s*x\s*([0-9]+)\s*\)\s*$/', $expr, $matches)) {
        list(, $period, $unit, $count) = $matches;
        $retryPolicy = new RetryPolicy($level, $count, strtotime("+{$period} {$unit}")-time());
        $policies[] = $retryPolicy;
        $level++;
      }
      else {
        throw new \InvalidArgumentException("Malformed retry expression [$expr]");
      }
    }
    return $policies;
  }

  /**
   * Given a set of a policies and the latest level+count, figure out the next
   * level+count.
   *
   * @param array $retryPolicies
   *   Array<RetryPolicy>.
   * @param int $lastLevel
   *   The last $level recorded for the cxn.
   * @param int $lastCount
   *   The last $count recorded for the cxn.
   * @param bool $success
   *   Whether the poll is a success or failure.
   *   (Successes reset to the base level. Failures increment the count/escalate level.)
   * @return array
   *   array($newLevel, $newCount)
   */
  public static function next($retryPolicies, $lastLevel, $lastCount, $success) {
    if ($success) {
      return array(0, 0);
    }
    foreach ($retryPolicies as $retryPolicy) {
      /** @var RetryPolicy $retryPolicy */
      if ($lastLevel == $retryPolicy->getLevel()) {
        if ($lastCount + 1 < $retryPolicy->getCount()) {
          return array($lastLevel, $lastCount + 1);
        }
        else {
          return array($lastLevel + 1, 0);
        }
      }
    }
    return array($lastLevel + 1, 0);
  }

  /**
   * @var int
   *   The (de)escalation level.
   */
  private $level;

  /**
   * @var int
   *   Number of times to retry within this level
   */
  private $count;

  /**
   * @var int
   *   The period of time (seconds) to wait between invocations
   *   within this level.
   */
  private $period;

  /**
   * RetryPolicy constructor.
   * @param int $level
   * @param int $count
   * @param int $period
   */
  public function __construct($level, $count, $period) {
    $this->level = $level;
    $this->count = $count;
    $this->period = $period;
  }

  /**
   * @return int
   */
  public function getLevel() {
    return $this->level;
  }

  /**
   * @return int
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * @return int
   */
  public function getPeriod() {
    return $this->period;
  }

}
