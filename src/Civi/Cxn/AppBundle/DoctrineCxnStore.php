<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Civi\Cxn\Rpc\Cxn;
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
    /** @var CxnEntity $cxnEntity */
    $cxnEntity = $this->em->getRepository('Civi\Cxn\AppBundle\Entity\CxnEntity')->findOneBy(array(
      'cxnId' => $cxn['cxnId'],
    ));
    if ($cxnEntity) {
      $cxnEntity->mergeArray($cxn);
    }
    else {
      $cxnEntity = new CxnEntity();
      $cxnEntity->mergeArray($cxn);
      $cxnEntity->setBatchCode(rand(BatchHelper::BATCH_CODE_MIN, BatchHelper::BATCH_CODE_MAX));
      $this->em->persist($cxnEntity);
    }

    $this->em->flush($cxnEntity);
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
      $this->em->flush($cxnEntity);
    }
  }

}
