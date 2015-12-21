<?php

namespace Civi\Cxn\DbCacheBundle;

use Civi\Cxn\DbCacheBundle\Entity\DbCacheRow;
use Civi\Cxn\Rpc\Time;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMException;

class DbCache extends CacheProvider {

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var EntityRepository
   */
  protected $repo;

  /**
   * DbCache constructor.
   * @param \Doctrine\ORM\EntityManager $em
   */
  public function __construct(\Doctrine\ORM\EntityManager $em) {
    $this->em = $em;
    $this->repo = $this->em->getRepository('Civi\Cxn\DbCacheBundle\Entity\DbCacheRow');
  }

  /**
   * {@inheritdoc}
   */
  protected function doFetch($id) {
    /** @var DbCacheRow $dbCacheRow */
    $dbCacheRow = $this->repo->find($id);
    if ($dbCacheRow === NULL || $dbCacheRow->isExpired()) {
      return FALSE;
    }
    else {
      return $dbCacheRow->getData();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doContains($id) {
    /** @var DbCacheRow $dbCacheRow */
    $dbCacheRow = $this->repo->find($id);
    if ($dbCacheRow === NULL || $dbCacheRow->isExpired()) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, $data, $lifeTime = 0) {
    if ($lifeTime > 0) {
      $expires = new \DateTime();
      $expires->setTimestamp(Time::getTime() + $lifeTime);
    }
    else {
      $expires = NULL;
    }

    $dbCacheRow = $this->repo->find($id);
    if (!$dbCacheRow) {
      $dbCacheRow = new DbCacheRow($id);
      $this->em->persist($dbCacheRow);
    }
    $dbCacheRow->setData($data);
    $dbCacheRow->setExpires($expires);

    try {
      $this->em->flush($dbCacheRow);
      return TRUE;
    }
    catch (ORMException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($id) {
    $dbCacheRow = $this->repo->find($id);
    if (!$dbCacheRow) {
      return TRUE;
    }

    try {
      $this->em->remove($dbCacheRow);
      $this->em->flush($dbCacheRow);
      return TRUE;
    }
    catch (ORMException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doFlush() {
    try {
      $this->em->createQuery('DELETE FROM Civi\Cxn\DbCacheBundle\Entity\DbCacheRow')->execute();
      $this->repo->clear();
      return TRUE;
    }
    catch (ORMException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doFetchMultiple(array $keys) {
    $dbCacheRows = $this->repo->findBy(array(
      'id' => $keys,
    ));

    $result = array();
    foreach ($dbCacheRows as $dbCacheRow) {
      /** @var DbCacheRow $dbCacheRow */
      if (!$dbCacheRow->isExpired()) {
        $result[$dbCacheRow->getId()] = $dbCacheRow->getData();
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetStats() {
    return NULL;
  }

  /**
   * @return EntityManager
   */
  public function getEntityManager() {
    return $this->em;
  }

  /**
   * @param EntityManager $em
   * @return $this
   */
  public function setEntityManager($em) {
    $this->em = $em;
    return $this;
  }

}
