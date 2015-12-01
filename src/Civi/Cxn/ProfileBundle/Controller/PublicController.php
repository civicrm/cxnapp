<?php

namespace Civi\Cxn\ProfileBundle\Controller;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\ProfileBundle\Entity\ProfileSettings;
use Civi\Cxn\ProfileBundle\Entity\ProfileSnapshot;
use Civi\Cxn\ProfileBundle\Form\ProfileSettingsType;
use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends Controller {

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var EntityRepository
   */
  protected $settingsRepo;

  /**
   * @var EntityRepository
   */
  protected $snapshotRepo;

  public function __construct(ContainerInterface $container, EntityManager $em) {
    $this->setContainer($container);
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSettings');
    $this->snapshotRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSnapshot');
  }

  /**
   * Display all ProfileSnapshots for a given site.
   *
   * @param Request $request
   * @param string $pubId
   *   Site ID.
   * @return Response
   */
  public function viewSiteAction(Request $request, $pubId) {
    /** @var ProfileSettings $settings */
    $settings = $this->settingsRepo->findOneBy(array('pubId' => $pubId));
    if (!$settings) {
      throw $this->createNotFoundException('Invalid profile ID');
    }

    $this->checkExpiration($settings);

    $snapshots = $this->snapshotRepo->findBy(
      array('cxn' => $settings->getCxn()),
      array('timestamp' => 'DESC')
    );

    return $this->render('CiviCxnProfileBundle:Public:site.html.twig', array(
      'settings' => $settings,
      'snapshots' => $snapshots,
    ));
  }

  /**
   * Display a single ProfileSnapshot.
   *
   * @param Request $request
   * @param string $pubId
   *   Snapshot ID.
   * @return Response
   */
  public function viewSnapshotAction(Request $request, $pubId) {
    /** @var ProfileSnapshot $snapshot */
    $snapshot = $this->snapshotRepo->findOneBy(array('pubId' => $pubId));
    if (!$snapshot) {
      throw $this->createNotFoundException('Invalid snapshot ID');
    }

    /** @var ProfileSettings $settings */
    $settings = $this->settingsRepo->findOneBy(array(
      'cxn' => $snapshot->getCxn(),
    ));
    if (!$settings) {
      throw $this->createNotFoundException('Missing settings');
    }

    $this->checkExpiration($settings);

    return $this->render('CiviCxnProfileBundle:Public:snapshot.html.twig', array(
      'snapshot' => $snapshot,
    ));
  }

  /**
   * @param $settings
   */
  protected function checkExpiration($settings) {
    // getExpires() returns 'yyyy-mm-dd 00:00:00', but we want to allow through the
    // end of the day ('23:59:59')
    $grace = (24 * 60 * 60) - 1;
    if ($settings->getExpires() && $settings->getExpires()
        ->getTimestamp() + $grace <= Time::getTime()
    ) {
      throw $this->createAccessDeniedException('Access to this profile has expired.');
    }
  }

}
