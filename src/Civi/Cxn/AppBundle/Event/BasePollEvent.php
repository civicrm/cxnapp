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

namespace Civi\Cxn\AppBundle\Event;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\Rpc\ApiClient;

/**
 * Class BasePollEvent
 * @package Civi\Cxn\AppBundle\Event
 *
 */
class BasePollEvent extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var string
   *   Ex: 'app:org.civicrm.cron'.
   */
  private $appId;

  /**
   * @var array
   */
  private $appMeta;

  /**
   * @var string
   *   Ex: 'default'
   */
  private $jobName;

  /**
   * @var array
   *   Array(0 => int $firstBatchCode, 1 => int $lastBatchCode).
   */
  private $batchRange;

  /**
   * BasePollEvent constructor.
   *
   * @param string $appId
   * @param array $appMeta
   * @param string $jobName
   * @param array $batchRange
   */
  public function __construct($appId, array $appMeta, $jobName, array $batchRange) {
    $this->appId = $appId;
    $this->appMeta = $appMeta;
    $this->jobName = $jobName;
    $this->batchRange = $batchRange;
  }

  /**
   * @return string
   *   Ex: 'app:org.civicrm.cron'.
   */
  public function getAppId() {
    return $this->appId;
  }

  /**
   * @return array
   */
  public function getAppMeta() {
    return $this->appMeta;
  }

  /**
   * @return string
   */
  public function getJobName() {
    return $this->jobName;
  }

  /**
   * @return array
   *   Array(0 => int $firstBatchCode, 1 => int $lastBatchCode).
   */
  public function getBatchRange() {
    return $this->batchRange;
  }

}
