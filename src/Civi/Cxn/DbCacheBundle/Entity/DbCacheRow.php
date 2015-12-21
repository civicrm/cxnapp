<?php

namespace Civi\Cxn\DbCacheBundle\Entity;

use Civi\Cxn\Rpc\Time;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DbCacheRow
 *
 * A site's profile at a specific point in time.
 *
 * @ORM\Table(name="DbCacheRow")
 * @ORM\Entity
 */
class DbCacheRow {

  /**
   * @var string
   *
   * @ORM\Id @ORM\Column(type="string", length=255)
   */
  private $id;

  /**
   * @var \DateTime
   *
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $expires;

  /**
   * @var array
   *
   * @ORM\Column(name="data", type="array", nullable=false)
   */
  private $data;

  /**
   * DbCacheRow constructor.
   * @param string $id
   * @param array $data
   * @param \DateTime $expires
   */
  public function __construct($id, array $data = array(), \DateTime $expires = NULL) {
    $this->id = $id;
    $this->data = $data = array();
    $this->expires = $expires;
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param int $id
   * @return $this
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getExpires() {
    return $this->expires;
  }

  /**
   * @param \DateTime $expires
   * @return $this
   */
  public function setExpires($expires) {
    $this->expires = $expires;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getData() {
    // Schema says it stores an array, but we could get scalars. Coerce.
    return isset($this->data[0]) ? $this->data[0] : NULL;
  }

  /**
   * @param mixed $data
   * @return $this
   */
  public function setData($data) {
    // Schema says it stores an array, but we could get scalars. Coerce.
    $this->data = array($data);
    return $this;
  }

  /**
   * @return bool
   */
  public function isExpired() {
    if ($this->expires === NULL) {
      return FALSE;
    }
    return $this->expires->getTimestamp() < Time::getTime();
  }

}
