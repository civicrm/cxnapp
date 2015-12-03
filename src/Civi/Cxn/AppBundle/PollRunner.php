<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\AppBundle\BatchHelper;
use Civi\Cxn\AppBundle\Entity\CxnEntity;
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
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PollRunner
 * @package Civi\Cxn\AppBundle
 *
 * The PollRunner searches for connections which have not been polled recently - and then
 * fires the PollEvent for each.
 */
class PollRunner {
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
   * @var \Civi\Cxn\Rpc\AppStore\AppStoreInterface
   */
  protected $appStore;

  /**
   * @var \Civi\Cxn\Rpc\CxnStore\CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @var \Doctrine\ORM\EntityRepository
   */
  protected $pollStatusRepo;

  public function __construct(EntityManager $em, EventDispatcherInterface $dispatcher, LoggerInterface $log = NULL, AppStoreInterface $appStore, CxnStoreInterface $cxnStore) {
    $this->log = $log;
    $this->dispatcher = $dispatcher;
    $this->em = $em;
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
    $this->pollStatusRepo = $em->getRepository('Civi\Cxn\AppBundle\Entity\PollStatus');
  }

  /**
   * Visit a single connection for a single app/job.
   *
   * @param CxnEntity $cxnEntity
   * @param string $jobName
   * @param array $retryPolicies
   *   Array(RetryPolicy).
   * @throws EntityNotFoundException
   */
  public function runCxn($cxnEntity, $jobName, $retryPolicies) {
    $appId = $cxnEntity->getAppId();
    $batchRange = array($cxnEntity->getBatchCode(), array($cxnEntity->getBatchCode()));

    $this->log->info("Poll cxn [{appId} {jobName} {cxnId}]", array(
      'appId' => $appId,
      'jobName' => $jobName,
    ));

    $this->initNew($this->findNew($appId, $jobName, $batchRange), $appId, $jobName);

    /** @var PollStatus $pollStatus */
    $pollStatus = $this->pollStatusRepo->findOneBy(array(
      'cxn' => $cxnEntity,
      'jobName' => $jobName,
    ));
    if ($pollStatus === NULL) {
      throw new EntityNotFoundException("Failed to locate PollStatus record");
    }

    $appMeta = $this->appStore->getAppMeta($appId);
    $prePollEvent = new PrePollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array(
      "{$appId}:job={$jobName}:pre-poll",
      "civi_cxn.pre-poll"
    ), $prePollEvent);

    $this->doPoll($pollStatus, $retryPolicies);

    $postPollEvent = new PostPollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array(
      "{$appId}:job={$jobName}:post-poll",
      "civi_cxn.post-poll"
    ), $postPollEvent);
  }

  /**
   * Visit each of the pending connections for an app/batch.
   *
   * @param string $appId
   * @param string $jobName
   * @param array $batchRange
   * @param array $retryPolicies
   *   Array(RetryPolicy).
   */
  public function runApp($appId, $jobName, $batchRange, $retryPolicies) {
    $this->log->info("Poll app [{appId} {jobName}]", array(
      'appId' => $appId,
      'jobName' => $jobName,
    ));

    $this->initNew($this->findNew($appId, $jobName, $batchRange), $appId, $jobName);

    $appMeta = $this->appStore->getAppMeta($appId);
    $prePollEvent = new PrePollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array(
      "{$appId}:job={$jobName}:pre-poll",
      "civi_cxn.pre-poll"
    ), $prePollEvent);

    foreach ($retryPolicies as $level => $retryPolicy) {
      /** @var RetryPolicy $retryPolicy */
      $results = $this->findPending($appId, $jobName, $retryPolicy, $batchRange);
      foreach ($results as $pollStatus) {
        $this->doPoll($pollStatus, $retryPolicies);
      }
    }

    $postPollEvent = new PostPollEvent($appId, $appMeta, $jobName, $batchRange);
    $this->dispatchEach(array(
      "{$appId}:job={$jobName}:post-poll",
      "civi_cxn.post-poll"
    ), $postPollEvent);
  }

  /**
   * Initialize tracking records for any new, unvisited connections.
   *
   * @param array $newCxns
   *   Array(CxnEntity $cxn).
   * @param string $appId
   *   Ex: 'app:org.civicrm.cron'.
   * @param string $jobName
   * @return array
   *   Array(PollStatus $pollStatus).
   */
  protected function initNew($newCxns, $appId, $jobName) {
    $pollStatuses = array();
    foreach ($newCxns as $newCxn) {
      $pollStatus = new PollStatus();
      $pollStatus
        ->setCxn($newCxn)
        ->setJobName($jobName)
        ->setPollLevel(0)
        ->setPollCount(0)
        ->setLastRun(0);
      $this->em->persist($pollStatus);
      $pollStatuses[] = $pollStatus;
    }
    if (!empty($pollStatuses)) {
      $this->em->flush();
    }
    return $pollStatuses;
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

  /**
   * @param \Civi\Cxn\AppBundle\Entity\PollStatus $pollStatus
   * @param array $retryPolicies
   * @return array
   */
  protected function doPoll($pollStatus, $retryPolicies) {
    $appId = $pollStatus->getCxn()->getAppId();
    $appMeta = $this->appStore->getAppMeta($appId);
    $jobName = $pollStatus->getJobName();

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