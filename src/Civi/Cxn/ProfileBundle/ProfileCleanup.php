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
namespace Civi\Cxn\ProfileBundle;

use Civi\Cxn\AppBundle\Event\PollEvent;
use Civi\Cxn\ProfileBundle\Entity\ProfileSnapshot;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

/**
 * Class ProfileCleanup
 * @package Civi\Cxn\ProfileBundle
 *
 * Identify
 */
class ProfileCleanup {

  const DELETION_BATCH_SIZE = 10000;

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  /**
   * @var \Doctrine\ORM\EntityRepository
   */
  protected $snapshotRepository;

  /**
   * @var array
   *   Ex: array('errors'=>10, 'overall'=>100).
   */
  protected $limits;

  /**
   * @var
   *   Array<QueryBuilder>.
   *   Each QueryBuilder expects one parameter ('cxn') and
   *   returns a list of candidates which could be deleted.
   */
  protected $cleanupQueries = NULL;

  /**
   * @param \Doctrine\ORM\EntityManager $em
   * @param \Psr\Log\LoggerInterface $log
   * @param array $limits
   *   Ex: array('errors'=>10, 'overall'=>100).
   */
  public function __construct(EntityManager $em, LoggerInterface $log, $limits) {
    $this->em = $em;
    $this->log = $log;
    $this->snapshotRepository = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSnapshot');
    $this->limits = $limits;
    if (empty($this->limits['errors'])) {
      throw new \InvalidArgumentException("Missing limits [errors]");
    }
    if (empty($this->limits['overall'])) {
      throw new \InvalidArgumentException("Missing limits [overall]");
    }
  }

  public function cleanup(PollEvent $poll) {
    foreach ($this->getCleanupQueries() as $queryBuilder) {
      // Haven't been able to get $qb->delete() to work. Possibly because
      // DELETE...ORDER BY...LIMIT is not portable? Workaround: iterate
      // through each record.

      $query = $queryBuilder->getQuery()
        ->setParameter('cxn', $poll->getCxnEntity());
      foreach ($query->getResult() as $snapshot) {
        /** @var ProfileSnapshot $snapshot */
        $this->log->info('ProfileCleanup[{cxnId}]: Remove ProfileSnapshot#{id}', array(
          'cxnId' => $poll->getCxnEntity()->getCxnId(),
          'id' => $snapshot->getId(),
        ));
        $this->em->remove($snapshot);
      }
      $this->em->flush();
    }
  }

  /**
   * Get a list of (prototypical) queries for finding stale snapshots.
   *
   * @return array
   *   Array<QueryBuilder>.
   *   Each QueryBuilder expects one parameter ('cxn') and
   *   returns a list of candidates which could be deleted.
   */
  protected function getCleanupQueries() {
    if ($this->cleanupQueries === NULL) {
      // Delete non-flagged items before deleting flagged items.
      // Delete errors before deleting successes.
      $wheres = array(
        // string $where => int $maxKeepRecords
        'snap.cxn = :cxn AND snap.flagged = 0 AND snap.status != \'ok\'' => $this->limits['errors'],
        'snap.cxn = :cxn AND snap.flagged = 0' => $this->limits['overall'],
        'snap.cxn = :cxn AND snap.flagged = 1 AND snap.status != \'ok\'' => $this->limits['errors'],
        'snap.cxn = :cxn AND snap.flagged = 1' => $this->limits['overall'],
      );

      $this->cleanupQueries = array();
      foreach ($wheres as $where => $maxKeepRecords) {
        if (!is_numeric($maxKeepRecords) || $maxKeepRecords <= 0) {
          throw new \InvalidArgumentException("ProfileCleanup: Threshold would delete all records!");
        }

        $this->cleanupQueries[] = $this->snapshotRepository
          ->createQueryBuilder('snap')
          ->where($where)
          ->setFirstResult($maxKeepRecords)
          ->setMaxResults(self::DELETION_BATCH_SIZE)
          ->addOrderBy('snap.timestamp', 'DESC');
      }
    }

    return $this->cleanupQueries;
  }

}
