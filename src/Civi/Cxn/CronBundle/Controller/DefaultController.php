<?php

namespace Civi\Cxn\CronBundle\Controller;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
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
   * @param CxnEntity $cxnEntity
   * @return Response
   */
  public function settingsAction(Request $request, CxnEntity $cxnEntity) {
    $t = $this->get('translator');

    if (empty($cxnEntity) || !$cxnEntity->getCxnId()) {
      throw new \RuntimeException('Error: cxn was not automatically loaded.');
    }

    // Find or create the settings for this connection.
    $settings = $this->settingsRepo->find($cxnEntity->getCxnId());
    if (!$settings) {
      $settings = new CronSettings();
      $settings->setCxn($cxnEntity);
      $this->em->persist($settings);
    }

    // Prepare and process a form.
    $form = $this->createForm(new CronSettingsType(), $settings);
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
