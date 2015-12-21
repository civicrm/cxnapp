<?php

namespace Civi\Cxn\AddressCheckerBundle\Controller;

use Civi\Cxn\AddressCheckerBundle\AddressChecker;
use Civi\Cxn\Rpc\Time;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AddressCheckerController extends Controller {

  protected $cacheTtl = 900;

  public function indexAction(Request $request) {
    /** @var AddressChecker $addressChecker */
    $addressChecker = $this->get('civi_cxn_address_checker.address_checker');

    if ($request->getMethod() === 'GET' && !$request->get('callback')) {
      throw $this->createAccessDeniedException('GET requests must specify "callback" for JSONP');
    }

    if (!$request->get('url')) {
      throw $this->createNotFoundException();
    }

    $data = array(
      'result' => ('ok' === $addressChecker->checkUrl($request->get('url'))),
    );

    $expiration = new \DateTime();
    $expiration->setTimestamp(Time::getTime() + $this->cacheTtl);

    $jsonResponse = new JsonResponse($data, 200);
    $jsonResponse->setExpires($expiration);
    $jsonResponse->setCallback($request->get('callback'));
    return $jsonResponse;
  }

}
