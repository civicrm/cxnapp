<?php
namespace Civi\Cxn\AppBundle\EventListener;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Doctrine\ORM\EntityManager;
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

  /**
   * @var CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @var EntityManager
   */
  protected $em;

  public function __construct(CxnStoreInterface $cxnStore, EntityManager $em) {
    $this->cxnStore = $cxnStore;
    $this->em = $em;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->getRequest()->attributes->has('cxnToken')) {
      $cxnToken = $event->getRequest()->attributes->get('cxnToken');

      $cxns = $this->em->createQuery('
        SELECT ce
        FROM Civi\Cxn\AppBundle\Entity\CxnToken ct, Civi\Cxn\AppBundle\Entity\CxnEntity ce
        WHERE ct.cxnToken = :cxnToken
        AND ct.expires > CURRENT_TIMESTAMP()
        AND ct.cxnId = ce.cxnId
      ')
        ->setParameter('cxnToken', $cxnToken)
        ->getResult();

      if (count($cxns) != 1 || !($cxns[0] instanceof CxnEntity)) {
        $event->setResponse(new Response(
          'Invalid or expired connection token.',
          403, array()
        ));
        return;
      }

      $event->getRequest()->attributes->set('cxn', $cxns[0]->toArray());
    }
  }

}
