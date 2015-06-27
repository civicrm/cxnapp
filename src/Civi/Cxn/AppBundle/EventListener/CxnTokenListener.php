<?php
namespace Civi\Cxn\AppBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class CxnTokenListener
 * @package Civi\Cxn\AppBundle\EventListener
 *
 * Listen for requests which include a "cxnToken" in the URL
 * and automatically dereference the token. Place the "cxn"
 * object in the list of request attributes.
 */
class CxnTokenListener {

  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->getRequest()->attributes->has('cxnToken')) {
      $cxnToken = $event->getRequest()->attributes->get('cxnToken');
      if ($cxnToken /* is valid */) {
        $event->getRequest()->attributes->set('cxn', array(
          'cxnId' => 'FIXME.' . $cxnToken,
          'appId' => 'FIXME.' . $cxnToken,
          'perm' => array(),
        ));
      }
      else {
        $event->setResponse(new Response(
          'Invalid or expired connection token.',
          403, array()
        ));
      }
    }
  }

}
