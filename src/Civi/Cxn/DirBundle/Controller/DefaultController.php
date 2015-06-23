<?php

namespace Civi\Cxn\DirBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CiviCxnDirBundle:Default:index.html.twig', array('name' => $name));
    }
}
