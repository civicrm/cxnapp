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

use Civi\Cxn\AppBundle\Event\RegistrationServerEvent;
use Civi\Cxn\ProfileBundle\Entity\ProfileSettings;
use Doctrine\ORM\EntityManager;

/**
 * Class ProfileRegistration
 * @package Civi\Cxn\ProfileBundle
 *
 * Respond to registration events on behalf of the profile app.
 */
class ProfileRegistration {

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var EntityRepository
   */
  protected $settingsRepo;

  public function __construct(EntityManager $em) {
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSettings');
  }

  public function onRespond(RegistrationServerEvent $e) {
    if (!isset($e->response['is_error']) || $e->response['is_error']) {
      return;
    }

    $log = $e->registrationServer->getLog();

    // Ensure that there is a ProfileSetting record.
    $cxnEntity = $e->findCxnEntity($this->em);
    $settings = $this->settingsRepo->find($cxnEntity->getCxnId());
    if (!$settings) {
      $log->info('Create new ProfileSettings for {wireCxn}.', array('wireCxn' => $e->wireCxn['cxnId']));
      $settings = new ProfileSettings();
      $settings->setCxn($cxnEntity);
      $settings->setPubId(ProfileSettings::generatePubId($this->em));
      $this->em->persist($settings);
      $this->em->flush();
    }
    else {
      $log->info('Found existing ProfileSettings for {wireCxn}.', array('wireCxn' => $e->wireCxn['cxnId']));
    }

  }

}
