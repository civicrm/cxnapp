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
use Civi\Cxn\ProfileBundle\Entity\ProfileSiteId;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

/**
 * Class ProfileSiteIdPoller
 * @package Civi\Cxn\ProfileBundle
 *
 * Poll a connected site and store its site_id.
 */
class ProfileSiteIdPoller {

  protected $sizeLimit = 255;

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  /**
   * @var EntityRepository
   */
  protected $snapshotRepo;

  public function __construct(EntityManager $em, LoggerInterface $log) {
    $this->em = $em;
    $this->log = $log;
    $this->siteIdRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSiteId');
  }

  public function onPoll(PollEvent $poll) {
    $result = $poll->getApiClient()->callSafe('Setting', 'get', array(
      'debug' => 1,
      'version' => 3,
      'return' => 'site_id',
    ));

    if (!empty($result['is_error'])) {
      $this->log->warning('ProfileSiteIdPoller[{cxnId}]: Setting.get: Returned error {error}', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
        'error' => isset($result['error_message']) ? $result['error_message'] : NULL,
      ));
      $poll->setError(TRUE);
      return;
    }

    $rxSiteId = $this->getSiteId($result);
    // NULL is expected for, e.g., old point-releases of 4.6.x.
    if ($rxSiteId !== NULL && (!is_string($rxSiteId) || strlen($rxSiteId) > $this->sizeLimit)) {
      $this->log->warning('ProfileSiteIdPoller[{cxnId}]: Setting.get: Returned malformed site_id', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
      ));
      $poll->setError(TRUE);
      return;
    }

    /** @var ProfileSiteId $profileSiteId */
    $profileSiteId = $this->siteIdRepo->findOneBy(array(
      'cxn' => $poll->getCxnEntity(),
    ));
    if (!$profileSiteId) {
      $this->log->notice('ProfileSiteIdPoller[{cxnId}]: Setting.get: Add SiteId record ({siteId})', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
        'siteId' => $rxSiteId,
      ));
      $profileSiteId = new ProfileSiteId($poll->getCxnEntity(), Time::createDateTime(), $rxSiteId);
      $this->em->persist($profileSiteId);
      $this->em->flush($profileSiteId);
    }
    elseif ($profileSiteId && $profileSiteId->getSiteId() !== $rxSiteId) {
      $this->log->warning('ProfileSiteIdPoller[{cxnId}]: site_id has changed ({old} => {new})', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
        'old' => $profileSiteId->getSiteId(),
        'new' => $rxSiteId,
      ));
      $profileSiteId->setTimestamp(Time::createDateTime());
      $profileSiteId->setSiteId($rxSiteId);
      $this->em->flush($profileSiteId);
    }
    else {
      $this->log->debug('ProfileSiteIdPoller[{cxnId}]: Setting.get: Keep SiteId record ({siteId})', array(
        'cxnId' => $poll->getCxnEntity()->getCxnId(),
        'siteId' => $rxSiteId,
      ));
    }
  }

  /**
   * @param array $result
   * @return mixed
   */
  protected function getSiteId($result) {
    // There could be several results for multidomain cfg. Choose the first.
    $values = isset($result['values']) ? $result['values'] : array();
    ksort($values);
    foreach ($values as $settings) {
      if (!empty($settings['site_id'])) {
        return $settings['site_id'];
      }
    }
    return NULL;
  }

}
