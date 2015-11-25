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
 * Class PollEvent
 * @package Civi\Cxn\AppBundle\Event
 *
 */
class PollEvent extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var \Civi\Cxn\Rpc\ApiClient
   */
  private $apiClient;

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
   * @var CxnEntity
   */
  private $cxnEntity;

  /**
   * @var bool
   */
  private $error = FALSE;

  /**
   * PollEvent constructor.
   *
   * @param string $appId
   *   Ex: 'app:org.civicrm.cron'.
   * @param string $jobName
   *   Ex: 'default'.
   * @param ApiClient $apiClient
   * @param array $appMeta
   * @param \Civi\Cxn\AppBundle\Entity\CxnEntity $cxnEntity
   */
  public function __construct($appId, $jobName, \Civi\Cxn\AppBundle\Entity\CxnEntity $cxnEntity, ApiClient $apiClient, $appMeta) {
    $this->apiClient = $apiClient;
    $this->appMeta = $appMeta;
    $this->appId = $appId;
    $this->jobName = $jobName;
    $this->cxnEntity = $cxnEntity;
  }

  /**
   * @return ApiClient
   */
  public function getApiClient() {
    return $this->apiClient;
  }

  /**
   * @return string
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
   * @return CxnEntity
   */
  public function getCxnEntity() {
    return $this->cxnEntity;
  }

  /**
   * @return boolean
   */
  public function isError() {
    return $this->error;
  }

  /**
   * @param boolean $error
   */
  public function setError($error) {
    $this->error = $error;
  }

}
