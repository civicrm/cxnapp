<?php

namespace Civi\Cxn\CrlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CiviCxnCrlBundle:Default:index.html.twig', array('name' => $name));
    }
}
