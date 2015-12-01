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
 * Class ProfilePoller
 * @package Civi\Cxn\ProfileBundle
 *
 * Poll a connected site and store its profile.
 */
class ProfilePoller {

  protected $sizeLimit = 786432;

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  public function __construct(EntityManager $em, LoggerInterface $log) {
    $this->em = $em;
    $this->log = $log;
  }

  public function onPoll(PollEvent $poll) {
    $result = $poll->getApiClient()->callSafe('System', 'get', array(
      'debug' => 1,
      'version' => 3,
    ));

    $size = $this->getSize($result);
    if ($size >= $this->sizeLimit) {
      $result = array(
        'is_error' => 1,
        'error_message' => 'ProfilePoller: Oversized response: ' . $size,
      );
      $this->log->warning('ProfilePoller[{cxnId}]: System.get: Returned oversized message (~{size})', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
        'size' => $size
      ));
    }

    $status = ProfileSnapshot::parseStatus($result);
    $pubId = ProfileSnapshot::generatePubId();

    $this->log->info("ProfilePoller[{cxnId}]: System.get: Returned {status}", array(
      'cxnId' => $poll->getCxnEntity()->getCxnId(),
      'status' => $status,
    ));

    $snapshot = new ProfileSnapshot(
      $poll->getCxnEntity(), $status, $result, Time::createDateTime(), 0, $pubId);
    $this->em->persist($snapshot);
    $this->em->flush($snapshot);

    $poll->setError($status != 'ok');
  }

  public function getSize($result) {
    return strlen(json_encode($result));
  }

}
