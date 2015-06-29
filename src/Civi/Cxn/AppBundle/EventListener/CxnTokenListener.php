<?php
namespace Civi\Cxn\AppBundle\EventListener;

use Civi\Cxn\AppBundle\CxnLinks;
use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class CxnTokenListener
 * @package Civi\Cxn\AppBundle\EventListener
 *
 * Listen for requests which include `cxnId`. If `cxnId` is present,
 * validate access and then export the $cxn array in the request attributes.
 *
 * Access to `cxnId` can be validated in one of two ways:
 *  - `cxnToken` is present, includes a hash and non-expired timestamp.
 *  - `cxnToken` was previously validated in the same session, and its timestamp is still valid.
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

  /**
   * @var CxnLinks
   */
  protected $cxnLinks;

  public function __construct(CxnStoreInterface $cxnStore, EntityManager $em, CxnLinks $cxnLinks) {
    $this->cxnStore = $cxnStore;
    $this->em = $em;
    $this->cxnLinks = $cxnLinks;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    if (!$event->getRequest()->attributes->has('cxnId')) {
      return;
    }

    $cxnId = $event->getRequest()->attributes->get('cxnId');
    $cxnSessionVar = 'cxn_' . md5($cxnId);
    if (empty($cxnId)) {
      $event->setResponse(new Response(
        'Invalid or expired connection token.',
        403, array()
      ));
      return;
    }

    $expires = $this->cxnLinks->checkToken($cxnId, $event->getRequest()->get('cxnToken'));
    if (empty($expires) && $event->getRequest()->getSession()->has($cxnSessionVar)) {
      $expires = $event->getRequest()->getSession()->get($cxnSessionVar);
    }
    elseif ($expires !== NULL) {
      $event->getRequest()->getSession()->set($cxnSessionVar, $expires);
    }

    if (!is_numeric($expires) || $expires <= time()) {
      $event->setResponse(new Response(
        'Invalid or expired connection token.',
        403, array()
      ));
      return;
    }

    $cxns = $this->em->createQuery('
        SELECT ce
        FROM Civi\Cxn\AppBundle\Entity\CxnEntity ce
        WHERE ce.cxnId = :cxnId
      ')
      ->setParameter('cxnId', $cxnId)
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
