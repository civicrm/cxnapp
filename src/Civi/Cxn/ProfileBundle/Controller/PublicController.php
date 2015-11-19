<?php

namespace Civi\Cxn\ProfileBundle\Controller;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\ProfileBundle\Entity\ProfileSettings;
use Civi\Cxn\ProfileBundle\Form\ProfileSettingsType;
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

  public function __construct(ContainerInterface $container, EntityManager $em) {
    $this->setContainer($container);
    $this->em = $em;
    $this->settingsRepo = $em->getRepository('Civi\Cxn\ProfileBundle\Entity\ProfileSettings');
  }

  /**
   * @param Request $request
   * @return Response
   */
  public function viewAction(Request $request, $pubId) {
    $settings = $this->settingsRepo->findOneBy(array('pubId' => $pubId));
    if (!$settings) {
      throw $this->createNotFoundException('Invalid profile ID');
    }

    return $this->render('CiviCxnProfileBundle:Public:profile.html.twig', array(
      'settings' => $settings,
    ));
  }

}
