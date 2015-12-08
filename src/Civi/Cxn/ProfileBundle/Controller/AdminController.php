<?php

namespace Civi\Cxn\ProfileBundle\Controller;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\AppBundle\PollRunner;
use Civi\Cxn\AppBundle\RetryPolicy;
use Civi\Cxn\ProfileBundle\Entity\ProfileSettings;
use Civi\Cxn\ProfileBundle\Event\SnapshotEvent;
use Civi\Cxn\ProfileBundle\Form\ProfileSettingsType;
use Civi\Cxn\ProfileBundle\ProfileEvents;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller {

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

  /**
   * @var \Civi\Cxn\AppBundle\PollRunner
   */
  protected $pollRunner;

  protected $snapshotLimit = 10;

  public function __construct(ContainerInterface $container, EntityManager $em, PollRunner $pollRunner) {
    $this->setContainer($container);
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSettings');
    $this->snapshotRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSnapshot');
    $this->pollRunner = $pollRunner;
  }

  /**
   * @param Request $request
   * @param CxnEntity $cxnEntity
   * @return Response
   */
  public function settingsAction(Request $request, CxnEntity $cxnEntity) {
    $t = $this->get('translator');

    if (empty($cxnEntity) || !$cxnEntity->getCxnId()) {
      throw $this->createNotFoundException('Error: cxn was not automatically loaded.');
    }

    // Find or create the settings for this connection.
    /** @var ProfileSettings $settings */
    $settings = $this->settingsRepo->find($cxnEntity->getCxnId());
    if (!$settings) {
      throw $this->createNotFoundException('Failed to find ProfileSettings record.');
    }

    $snapshots = $this->findCreateSnapshots($settings);

    // Prepare and process a form.
    $form = $this->createForm(new ProfileSettingsType(), $settings);
    $form->handleRequest($request);
    if ($form->isValid()) {
      $this->get('session')->getFlashBag()->add(
        'notice',
        $t->trans('Saved')
      );
      $this->em->flush();
    }

    return $this->render('CiviCxnProfileBundle:Admin:settings.html.twig', array(
      'form' => $form->createView(),
      'settings' => $settings,
      'snapshots' => $snapshots,
      'timezone' => date('T'),
    ));
  }

  /**
   * Fetch a new ProfileSnapshot and redirect.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Civi\Cxn\AppBundle\Entity\CxnEntity $cxnEntity
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function refreshAction(Request $request, CxnEntity $cxnEntity) {
    $t = $this->get('translator');

    if (empty($cxnEntity) || !$cxnEntity->getCxnId()) {
      throw $this->createNotFoundException('Error: cxn was not automatically loaded.');
    }

    // Find or create the settings for this connection.
    /** @var ProfileSettings $settings */
    $settings = $this->settingsRepo->find($cxnEntity->getCxnId());
    if (!$settings) {
      throw $this->createNotFoundException('Failed to find ProfileSettings record.');
    }

    if ($request->getMethod() !== 'POST') {
      $this->createAccessDeniedException('This request must be submitted via POST');
    }

    $onSnapshot = function (SnapshotEvent $e) use ($t) {
      $e->getSnapshot()->setFlagged(TRUE);
      $this->get('session')->getFlashBag()->add(
        'notice',
        $t->trans('Refreshed')
      );
    };
    $this->get('event_dispatcher')->addListener(ProfileEvents::SNAPSHOT, $onSnapshot);

    $this->pollRunner->runCxn($settings->getCxn(), 'default', $this->getRetryPolicies());

    return $this->redirect($this->generateUrl('org_civicrm_profile_settings', array(
      'cxnId' => $cxnEntity->getCxnId(),
    )));
  }

  /**
   * @return array
   */
  protected function getRetryPolicies() {
    $max = 10 * 365 * 24 * 60;
    return RetryPolicy::parse("1min (x{$max})");
  }

  /**
   * @param ProfileSettings $settings
   * @return array
   *   Array(array $snapshots, ProfileSnapshot $lastError)
   * @throws \Doctrine\ORM\EntityNotFoundException
   */
  protected function findCreateSnapshots($settings) {
    $t = $this->get('translator');

    $snapshots = $this->snapshotRepo->findBy(
      array('cxn' => $settings->getCxn()),
      array('timestamp' => 'DESC'),
      $this->snapshotLimit
    );

    if (!empty($snapshots)) {
      return $snapshots;
    }

    $this->pollRunner->runCxn($settings->getCxn(), 'default', $this->getRetryPolicies());
    $snapshots = $this->snapshotRepo->findBy(
      array('cxn' => $settings->getCxn()),
      array('timestamp' => 'DESC'),
      $this->snapshotLimit
    );

    if (!empty($snapshots)) {
      return $snapshots;
    }

    // Note: This should never -- even in cases where the PollRunner fails to
    // get good data, it should create a ProfileSnapshot with the error data.
    $this->get('session')->getFlashBag()->add(
      'error',
      $t->trans('Failed to find or initialize a snapshot.')
    );
    return array();

  }

}
