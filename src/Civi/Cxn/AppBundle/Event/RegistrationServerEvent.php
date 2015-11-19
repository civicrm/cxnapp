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
use Civi\Cxn\Rpc\RegistrationServer;

/**
 * Class RegistrationServerEvent
 * @package Civi\Cxn\AppBundle
 *
 * The AppRegistrationServer accepts messages using APIv3 structure (Entity+Action+Params).
 * It emits RegistrationServerEvents before and after executing these requests.
 */
class RegistrationServerEvent extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var RegistrationServer
   */
  public $registrationServer;

  /**
   * @var array
   *   The CXN record submitted by the client.
   *
   *   Tip: To access the persistent CXN record, use
   *   $cxnStore->getByCxnId($event->wireCxn['cxnId']);
   */
  public $wireCxn;

  /**
   * @var string
   */
  public $entity;

  /**
   * @var string
   */
  public $action;

  /**
   * @var array
   *   Additional parameters from the client.
   */
  public $params;

  /**
   * @var array|NULL
   *   The response document. If the response has not been determined, NULL.
   */
  public $response;

  /**
   * RegistrationEvent constructor.
   *
   * @param RegistrationServer $server
   * @param array $wireCxn
   * @param string $entity
   * @param string $action
   * @param array $params
   */
  public function __construct(RegistrationServer $server, array $wireCxn, $entity, $action, array $params, $response = NULL) {
    $this->registrationServer = $server;
    $this->wireCxn = $wireCxn;
    $this->entity = $entity;
    $this->action = $action;
    $this->params = $params;
    $this->response = $response;
  }

  /**
   * Create a formatted response which indicates success.
   *
   * @param array $values
   * @return array
   */
  public function respondSuccess($values) {
    $this->response = array(
      'is_error' => 0,
      'values' => $values,
    );
    $this->stopPropagation();
    return $this->response;
  }

  /**
   * Create a formatted response which indicates failure.
   *
   * @param string $message
   * @return array
   */
  public function respondError($message) {
    $this->response = array(
      'is_error' => 1,
      'error_message' => $message,
    );
    $this->stopPropagation();
    return $this->response;
  }

}
