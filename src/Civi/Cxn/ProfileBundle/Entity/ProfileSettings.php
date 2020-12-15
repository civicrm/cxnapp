<?php

namespace Civi\Cxn\ProfileBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileSettings
 *
 * @ORM\Table(name="ProfileSettings",
 *   uniqueConstraints={@ORM\UniqueConstraint(name="pubIdx",columns={"pubId"})},
 * )
 * @ORM\Entity
 */
class ProfileSettings {

  /**
   * @var CxnEntity
   *
   * @ORM\Id
   * @ORM\OneToOne(targetEntity="Civi\Cxn\AppBundle\Entity\CxnEntity")
   * @ORM\JoinColumn(name="cxnId", referencedColumnName="cxnId", onDelete="CASCADE")
   */
  private $cxn;

  /**
   * @var \DateTime
   *
   * @ORM\Column(type="date", nullable=true)
   */
  private $expires;

  /**
   * @var string
   *
   * @ORM\Column(name="pubId", type="string", length=32, nullable=false)
   */
  private $pubId;

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
   * Helper functions for generating a unique pubId.
   *
   * A pubId looks like "Ab1C2-dE3f4-g5Hi6".
   *
   * @param \Doctrine\ORM\EntityManager $em
   * @param int $bytes
   *   Number of random bytes to gather from RNG.
   * @param int $chars
   *   Number of characters to generate.
   * @param int $spacing
   *   Format the pubId with a delimiter every X chars.
   * @param int $tries
   * @return mixed
   */
  public static function generatePubId(EntityManager $em, $bytes = 20, $chars = 15, $spacing = 5, $tries = 5) {
    do {
      $tries--;

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

      $count = $em
        ->createQuery('
            SELECT count(ps.pubId) FROM Civi\Cxn\ProfileBundle\Entity\ProfileSettings ps
             WHERE ps.pubId = :pubId
          ')
        ->setParameter('pubId', $pubId)
        ->getSingleScalarResult();
    } while ($tries > 0 && $count > 0);

    if ($count > 0) {
      throw new \RuntimeException("Failed to generate unique public ID");
    }

    return $pubId;
  }

}
