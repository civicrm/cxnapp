<?php

namespace Civi\Cxn\ProfileBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileSettings
 *
 * @ORM\Table("ProfileSettings")
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
   * @ORM\Column(name="pubId", type="string", length=128, nullable=true)
   * @Assert\Email
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

}
