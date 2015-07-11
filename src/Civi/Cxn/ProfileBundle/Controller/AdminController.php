<?php

namespace Civi\Cxn\ProfileBundle\Controller;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\ProfileBundle\Entity\ProfileSettings;
use Civi\Cxn\ProfileBundle\Form\ProfileSettingsType;
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

  public function __construct(ContainerInterface $container, EntityManager $em) {
    $this->setContainer($container);
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSettings');
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
      $settings = new ProfileSettings();
      $settings->setCxn($cxnEntity);
      $this->em->persist($settings);
    }

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
    ));
  }

}
