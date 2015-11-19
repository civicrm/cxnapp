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

/**
 * Events which fire before and after handling a change to the Cxn registration.
 *
 * Every event in this class comes in two variants:
 *  - A global event "civi_cxn_registration_server.{phase}"
 *    which may be fired for *any* request handled by the registration server.
 *  - A dynamic, targeted event "{appId}:{entity}.{action}:{phase}".
 *    which only fires for specific applications/actions.
 */
class RegistrationServerEvents {

  /**
   * To modify the request data, listen to the PARSE event and read/write values
   * in $event.
   *
   * This variant of the event may handle parsing for *any* request.
   *
   * For more precise targeting, you may use the dynamic version of this event.
   * - Formula: "{appId}:{entity}.{action}:{phase}".
   * - Example: "app:org.civicrm.profile:cxn.register:parse".
   */
  const PARSE = 'civi_cxn_registration_server.parse';

  /**
   * To define a custom request handler (e.g. to add/override an entity
   * or action), listen to the CALL event and modify $event->response.
   *
   * This variant of the event may handle calling for *any* request.
   *
   * For more precise targeting, you may use the dynamic version of this event.
   * - Formula: "{appId}:{entity}.{action}:{phase}".
   * - Example: "app:org.civicrm.profile:cxn.register:call".
   */
  const CALL = 'civi_cxn_registration_server.call';

  /**
   * To modify the response data, listen to the RESPOND event and read/write values
   * in $event->response.
   *
   * This variant of the event may handle responding for *any* request.
   *
   * For more precise targeting, you may use the dynamic version of this event.
   * - Formula: "{appId}:{entity}.{action}:{phase}".
   * - Example: "app:org.civicrm.profile:cxn.register:respond".
   */
  const RESPOND = 'civi_cxn_registration_server.respond';

}
