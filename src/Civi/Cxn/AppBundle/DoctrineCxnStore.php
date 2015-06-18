<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Doctrine\ORM\EntityManager;

class DoctrineCxnStore implements CxnStoreInterface {

  /**
   * @var EntityManager
   */
  protected $em;

  public function __construct(EntityManager $em) {
    $this->em = $em;
  }

  /**
   * {@inheritDoc}
   */
  public function getAll() {
    $result = array();
    foreach ($this->em->getRepository('Civi\Cxn\AppBundle\Entity\CxnEntity')->findAll() as $cxnEntity) {
      /** @var $cxnEntity CxnEntity */
      $result[$cxnEntity->getCxnId()] = $cxnEntity->toArray();
    }
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getByCxnId($cxnId) {
    $cxnEntity = $this->em->getRepository('Civi\Cxn\AppBundle\Entity\CxnEntity')->findOneBy(array(
      'cxnId' => $cxnId,
    ));
    return $cxnEntity ? $cxnEntity->toArray() : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getByAppId($appId) {
    $cxnEntity = $this->em->getRepository('Civi\Cxn\AppBundle\Entity\CxnEntity')->findOneBy(array(
      'appId' => $appId,
    ));
    return $cxnEntity ? $cxnEntity->toArray() : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function add($cxn) {
    $cxnEntity = CxnEntity::create($cxn);
    $this->em->persist($cxnEntity);
  }

  /**
   * {@inheritDoc}
   */
  public function remove($cxnId) {
    $cxnEntity = $this->em->getRepository('Civi\Cxn\AppBundle\Entity\CxnEntity')->findOneBy(array(
      'cxnId' => $cxnId,
    ));
    if ($cxnEntity) {
      $this->em->remove($cxnEntity);
    }
  }

}
