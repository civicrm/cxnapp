<?php

namespace Civi\Cxn\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CiviCxnCronBundle:Default:index.html.twig', array('name' => $name));
    }
}
