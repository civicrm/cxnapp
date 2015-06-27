<?php

namespace Civi\Cxn\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {

  /**
   * @param Request $request
   * @return Response
   */
  public function settingsAction(Request $request) {
    return $this->render('CiviCxnCronBundle:Default:index.html.twig', array('name' => json_encode($request->attributes->get('cxn'))));
  }

}
