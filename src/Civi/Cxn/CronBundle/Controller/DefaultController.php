<?php

namespace Civi\Cxn\CronBundle\Controller;

use Civi\Cxn\CronBundle\Entity\CronSettings;
use Civi\Cxn\CronBundle\Form\CronSettingsType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

  /**
   * @var EntityManager
   */
  protected $em;

  /**
   * @var EntityRepository
   */
  protected $settingsRepo;

  public function __construct(ContainerInterface $container, EntityManager $em) {
    $this->setContainer($container);
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\CronBundle\Entity\CronSettings');
  }

  /**
   * @param Request $request
   * @return Response
   */
  public function settingsAction(Request $request) {
    $t = $this->get('translator');

    $cxn = $request->attributes->get('cxn');
    if (empty($cxn) || empty($cxn['cxnId'])) {
      throw new \RuntimeException('Error: cxn was not automatically loaded.');
    }

    // Find or create the settings for this connection.
    $cronSettings = $this->settingsRepo->find($cxn['cxnId']);
    if (!$cronSettings) {
      $cronSettings = new CronSettings();
      $cronSettings->setCxnId($cxn['cxnId']);
      $this->em->persist($cronSettings);
    }

    // Prepare and process a form.
    $form = $this->createForm(new CronSettingsType(), $cronSettings);
    $form->handleRequest($request);
    if ($form->isValid()) {
      $this->get('session')->getFlashBag()->add(
        'notice',
        $t->trans('Saved')
      );
      $this->em->flush();
    }

    return $this->render('CiviCxnCronBundle:Default:settings.html.twig', array(
      'form' => $form->createView(),
    ));
  }

}
