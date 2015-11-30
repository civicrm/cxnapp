<?php

namespace Civi\Cxn\ProfileBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileSnapshot
 *
 * A site's profile at a specific point in time.
 *
 * @ORM\Table(name="ProfileSnapshot",
 *   indexes={
 *     @ORM\Index(name="cleanupIdx", columns={"cxnId","flagged","timestamp"})
 *   }
 * )
 * @ORM\Entity
 */
class ProfileSnapshot {

  /**
   * @var integer
   *
   * @ORM\Id @ORM\Column(type="integer")
   * @ORM\GeneratedValue
   */
  private $id;

  /**
   * @var CxnEntity
   *
   * @ORM\ManyToOne(targetEntity="Civi\Cxn\AppBundle\Entity\CxnEntity")
   * @ORM\JoinColumn(name="cxnId", referencedColumnName="cxnId", onDelete="CASCADE")
   */
  private $cxn;

  /**
   * @var \DateTime
   *
   * @ORM\Column(type="datetime", nullable=false)
   */
  private $timestamp;

  /**
   * @var array
   *
   * @ORM\Column(name="response", type="json_array", nullable=false)
   */
  private $data;

  /**
   * @var string
   *  - 'ok': API call returned OK.
   *  - 'error': API call returned error.
   *  - 'exception': Exception raised while processing API call.
   *  - 'garbled': Response from server appeared malformed. Likely broken crypto.
   *
   * @ORM\Column(name="status", type="string", nullable=false)
   */
  private $status;

  /**
   * @var boolean
   *
   * Whether a user/admin has flagged this record for preservation.
   *
   * @ORM\Column(name="flagged", type="boolean", nullable=false)
   */
  private $flagged;

  /**
   * ProfileSnapshot constructor.
   * @param string $status
   * @param array $data
   * @param \DateTime $timestamp
   * @param \Civi\Cxn\AppBundle\Entity\CxnEntity $cxn
   */
  public function __construct(CxnEntity $cxn, $status, array $data, \DateTime $timestamp = NULL, $flagged = 0) {
    $this->status = $status;
    $this->data = $data;
    $this->timestamp = $timestamp;
    $this->cxn = $cxn;
    $this->flagged = $flagged;
  }

  /**
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return CxnEntity
   */
  public function getCxn() {
    return $this->cxn;
  }

  /**
   * @param CxnEntity $cxn
   * @return $this
   */
  public function setCxn($cxn) {
    $this->cxn = $cxn;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * @param \DateTime $timestamp
   * @return $this
   */
  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * @return array
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param array $data
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return boolean
   */
  public function isFlagged() {
    return $this->flagged;
  }

  /**
   * @param boolean $flagged
   */
  public function setFlagged($flagged) {
    $this->flagged = $flagged;
  }

  /**
   * @param array $data
   * @return string
   */
  public static function parseStatus($data) {
    if (empty($data['is_error'])) {
      $status = 'ok';
    }
    elseif (!empty($data['garbled_message'])) {
      $status = 'garbled';
    }
    elseif (!empty($data['trace'])) {
      $status = 'exception';
    }
    else {
      $status = 'error';
    }
    return $status;
  }

}
