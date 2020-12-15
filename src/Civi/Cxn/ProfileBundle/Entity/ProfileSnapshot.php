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
 *   uniqueConstraints={@ORM\UniqueConstraint(name="pubIdx",columns={"pubId"})},
 *   indexes={
 * @ORM\Index(name="cleanupIdx", columns={"cxnId","flagged","timestamp"}),
 * @ORM\Index(name="timestampIdx", columns={"cxnId","timestamp"})
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
   * @var string
   *
   * @ORM\Column(name="pubId", type="string", length=32, nullable=false)
   */
  private $pubId;

  /**
   * ProfileSnapshot constructor.
   * @param \Civi\Cxn\AppBundle\Entity\CxnEntity $cxn
   * @param string $status
   * @param array $data
   * @param \DateTime $timestamp
   * @param bool $flagged
   * @param string $pubId
   */
  public function __construct(CxnEntity $cxn, $status, array $data, \DateTime $timestamp = NULL, $flagged = 0, $pubId = NULL) {
    $this->status = $status;
    $this->data = $data;
    $this->timestamp = $timestamp;
    $this->cxn = $cxn;
    $this->flagged = $flagged;
    $this->pubId = $pubId;
  }

  /**
   * @return int
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
   * @return $this
   */
  public function setData($data) {
    $this->data = $data;
    return $this;
  }

  /**
   * @return string
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * @param string $status
   * @return $this
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * @return bool
   */
  public function isFlagged() {
    return $this->flagged;
  }

  /**
   * @param bool $flagged
   * @return $this
   */
  public function setFlagged($flagged) {
    $this->flagged = $flagged;
    return $this;
  }

  /**
   * @return string
   */
  public function getPubId() {
    return $this->pubId;
  }

  /**
   * @param string $pubId
   * @return $this
   */
  public function setPubId($pubId) {
    $this->pubId = $pubId;
    return $this;
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

  /**
   * Helper functions for generating a unique pubId.
   *
   * A pubId looks like "Ab1C2-dE3f4-g5Hi6".
   *
   * @param int $bytes
   *   Number of random bytes to gather from RNG.
   * @param int $chars
   *   Number of characters to generate.
   * @param int $spacing
   *   Format the pubId with a delimiter every X chars.
   * @return mixed
   */
  public static function generatePubId($bytes = 20, $chars = 20, $spacing = 5) {
    $raw = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(random_bytes($bytes)));
    // This conversion from bytes to alphanumerics is a bit lossy. To ensure we
    // end up with enough characters, we take a few extra bytes.

    // Take {$chars} alphanumerics, and intersperse dashes (AbC1-dE2f-gHi3).
    $pubId = '';
    for ($i = 0; $i < $chars; $i++) {
      if ($i > 0 && $i % $spacing == 0) {
        $pubId .= '-';
      }
      $pubId .= $raw{$i};
    }

    return $pubId;
  }

}
